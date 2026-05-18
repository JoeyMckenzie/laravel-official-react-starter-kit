{
  pkgs,
  lib,
  config,
  ...
}:

let
  # ─── Worktree identity ────────────────────────────────────────────────
  worktreeName =
    let
      base = builtins.baseNameOf (toString ./.);
    in
    if base == "laravel-official-react-starter-kit" then "main" else lib.toLower base;

  isPrimary = worktreeName == "main";

  # ─── Port index (hash-based, .devenv-index overrides) ─────────────────
  hexDigit =
    c:
    {
      "0" = 0;
      "1" = 1;
      "2" = 2;
      "3" = 3;
      "4" = 4;
      "5" = 5;
      "6" = 6;
      "7" = 7;
      "8" = 8;
      "9" = 9;
      "a" = 10;
      "b" = 11;
      "c" = 12;
      "d" = 13;
      "e" = 14;
      "f" = 15;
    }
    .${c};

  hashValue =
    let
      hex = builtins.substring 0 4 (builtins.hashString "sha256" worktreeName);
      chars = lib.stringToCharacters hex;
    in
    lib.foldl' (acc: c: acc * 16 + (hexDigit c)) 0 chars;

  # Wider modulo (500) drops birthday-collision probability from ~50% at 10
  # worktrees down to ~9%. If you hit one anyway, drop a `.devenv-index` file
  # with any unused 1-499 to pin this worktree's slot manually.
  hashIndex = (lib.mod hashValue 500) + 1;

  indexFile = ./.devenv-index;
  index =
    if isPrimary then
      0
    else if builtins.pathExists indexFile then
      lib.toInt (lib.removeSuffix "\n" (builtins.readFile indexFile))
    else
      hashIndex;

  # Redis DB number — hard ceiling at 64 (redis `--databases 64`). Collisions
  # here are harmless because each worktree also scopes via HORIZON_PREFIX
  # and SESSION_DOMAIN, so two worktrees sharing a DB number can coexist
  # without keyspace bleed.
  redisDbIndex = if isPrimary then 0 else (lib.mod hashValue 63) + 1;

  # Per-worktree ports. Postgres/Redis/Caddy/Mailpit are nix-darwin daemons
  # on their standard ports — see ~/.config/nix-darwin/darwin/services.nix.
  phpPort = 8000 + index;
  vitePort = 5173 + index;

  # ─── Names + hosts ────────────────────────────────────────────────────
  dbName =
    if isPrimary then
      "laravel_official_react_starter_kit"
    else
      "laravel_official_react_starter_kit_"
      + lib.replaceStrings [ "-" "." "/" ] [ "_" "_" "_" ] worktreeName;

  appHost =
    if isPrimary then
      "laravel-official-react-starter-kit.test"
    else
      "${worktreeName}.laravel-official-react-starter-kit.test";

  # vite.config.ts should set `server.origin` to this and `server.hmr.host`
  # to this with `protocol = 'wss'` + `clientPort = 443` so HMR works over
  # HTTPS without mixed-content blocks.
  viteHost = "vite.${appHost}";

  home = builtins.getEnv "HOME";
  caddySite = "${home}/.config/caddy/sites/laravel-official-react-starter-kit-${worktreeName}.caddy";
  caddySitesDir = "${home}/.config/caddy/sites";

  worktreesRoot = builtins.dirOf (toString ./.);
in
{
  dotenv.disableHint = true;

  claude = {
    code.enable = true;
    code.mcpServers = {
      devenv = {
        type = "stdio";
        command = "devenv";
        args = [ "mcp" ];
      };
      shadcn = {
        type = "stdio";
        command = "npx";
        args = [
          "shadcn@latest"
          "mcp"
        ];
      };
      boost = {
        type = "stdio";
        command = "php";
        args = [
          "artisan"
          "boost:mcp"
        ];
      };
    };
  };

  # ─── Languages ─────────────────────────────────────────────────────────
  languages.php = {
    enable = true;
    version = "8.4";
    extensions = [
      "bcmath"
      "gd"
      "zip"
      "pdo_pgsql"
      "redis"
      "opcache"
      "intl"
      "xdebug"
    ];
    ini = ''
      ${builtins.readFile ./php.ini.base}
      ${lib.optionalString (builtins.pathExists ./php.local.ini) (builtins.readFile ./php.local.ini)}
    '';
    fpm.pools.web = {
      settings = {
        "listen" = "127.0.0.1:${toString phpPort}";
        "pm" = "dynamic";
        "pm.max_children" = 10;
        "pm.start_servers" = 2;
        "pm.min_spare_servers" = 1;
        "pm.max_spare_servers" = 3;
        # Inherit the devenv shell env so DB_DATABASE / REDIS_DB /
        # SESSION_DOMAIN / HORIZON_PREFIX reach the FPM workers. Without
        # this, php-fpm scrubs the env and only the .env file is visible.
        "clear_env" = "no";
        # macOS fork-safety workaround for pdo_pgsql. libpq tries GSSAPI
        # encryption negotiation by default, which pulls Kerberos →
        # CoreFoundation prefs → mach IPC. The IPC state can't be reused
        # after fork(), so php-fpm workers SIGSEGV inside PQconnectdb.
        # Setting PGGSSENCMODE=disable tells libpq to skip the GSSAPI
        # codepath entirely — no kerberos init, no CFPrefs, no crash.
        # OBJC_DISABLE_… is kept as a belt-and-suspenders for any other
        # CF-touching library that might creep in. (Givebutter doesn't
        # hit this because pdo_mysql has no GSSAPI path.)
        "env[PGGSSENCMODE]" = "disable";
      };
    };
  };

  languages.javascript = {
    enable = true;
    package = pkgs.nodejs_22;
    npm.enable = true;
  };

  # ─── Services ─────────────────────────────────────────────────────────
  # Postgres, Redis, Caddy, and Mailpit run as nix-darwin daemons.
  # See ~/.config/nix-darwin/darwin/services.nix.

  # ─── CLI tooling ──────────────────────────────────────────────────────
  packages = with pkgs; [
    figlet
    postgresql_17
  ];

  # ─── Per-worktree env ─────────────────────────────────────────────────
  # Only worktree-dependent values live here. Static project config (DB
  # driver/host/creds, Redis host, mail host, etc.) lives in .env so that
  # artisan commands work the same whether or not the devenv shell is
  # active. Anything in this block overrides .env at runtime via
  # phpdotenv's immutable mode.
  env = {
    APP_URL = "https://${appHost}";
    APP_DOMAIN = appHost;
    ASSET_URL = "https://${appHost}";

    # Vite served behind caddy. VITE_DEV_SERVER_URL is what laravel-vite-
    # plugin emits in <script> tags; without it, asset URLs default to
    # http://localhost:VITE_PORT and the HTTPS app page blocks them as
    # mixed content.
    VITE_PORT = toString vitePort;
    VITE_DEV_SERVER_URL = "https://${viteHost}";

    HORIZON_DOMAIN = "horizon.${appHost}";
    HORIZON_PATH = "/";

    # Scope sessions to this worktree's subtree so two open worktrees
    # don't collide. dashboard.${worktreeName}.laravel_official_react_starter_kit.test is under
    # .${worktreeName}.laravel_official_react_starter_kit.test, so any subdomain sharing still works.
    SESSION_DOMAIN =
      if isPrimary then
        ".laravel-official-react-starter-kit.test"
      else
        ".${worktreeName}.laravel-official-react-starter-kit.test";

    # Per-worktree database name (cluster-shared role lives in .env).
    DB_DATABASE = dbName;

    # Redis DB number — mod 63 since Redis only has 64 DBs. Collisions are
    # harmless thanks to HORIZON_PREFIX scoping below.
    REDIS_DB = toString redisDbIndex;

    # Queue key prefix — keeps each worktree's queue keys isolated even
    # when redisDbIndex collides between worktrees.
    HORIZON_PREFIX = "horizon-${worktreeName}:";
  };

  processes = {
    # PHP-FPM is managed by languages.php.fpm above. If `devenv up` was
    # killed ungracefully and a php-fpm master is leaked on port phpPort,
    # the new master fails silently to bind and the OLD one serves requests
    # with stale env. Recovery: `pkill -f 'php-fpm: master'` then re-up.
    #
    # When `devenv up` is killed ungracefully, vite doesn't always propagate
    # SIGTERM either, leaving an orphan bound to the port. The pre-bind lsof
    # reclaims it; `exec` replaces the shell so SIGTERMs reach vite directly.
    vite.exec = ''
      pids=$(lsof -ti:${toString vitePort} 2>/dev/null || true)
      [ -n "$pids" ] && { echo "→ killing orphan on :${toString vitePort} ($pids)"; kill -9 $pids; }
      exec npm run dev -- --host 127.0.0.1 --port ${toString vitePort} --strictPort
    '';

    # Horizon + scheduler run in every worktree (against the shared redis;
    # HORIZON_PREFIX + REDIS_DB isolate keys per worktree).
    horizon.exec = "php artisan config:clear && php artisan horizon:listen";
    scheduler.exec = "php artisan config:clear && php artisan schedule:work";

    logs.exec = "php artisan pail --timeout=0";
  };

  # ─── Tasks: parallel bootstrap (run before devenv:enterShell) ─────────
  # Each task either skips via `status` (exit-0 = satisfied) or re-runs
  # when watched files change via `execIfModified` (content-hash tracked
  # in .devenv/ SQLite, so lockfile drift is auto-detected).
  tasks = {
    # Caddy — runs every entry (no status) to handle config drift.
    "caddy:write-site" = {
      description = "Write per-worktree Caddyfile";
      exec = ''
        set -euo pipefail
        mkdir -p "${caddySitesDir}"
        cat > "${caddySite}" <<EOF
        ${appHost}, horizon.${appHost} {
          root * ${config.devenv.root}/public
          php_fastcgi 127.0.0.1:${toString phpPort}
          encode zstd gzip
          file_server
        }

        ${viteHost} {
          reverse_proxy 127.0.0.1:${toString vitePort}
        }
        EOF
      '';
      before = [ "devenv:enterShell" ];
    };

    "caddy:reload" = {
      description = "Reload Caddy via admin API";
      exec = ''
        set -euo pipefail
        if curl -fsS --max-time 2 http://localhost:2019/config/ >/dev/null 2>&1; then
          if curl -fsS -X POST -H "Content-Type: text/caddyfile" \
               --data-binary @/etc/caddy/Caddyfile \
               "http://localhost:2019/load?adapter=caddyfile" >/dev/null; then
            echo "✅ caddy reloaded (${appHost})"
          else
            echo "⚠ caddy admin API rejected reload — check /etc/caddy/Caddyfile syntax"
          fi
        else
          echo "⚠ caddy admin API not reachable. Try: sudo launchctl kickstart -k system/org.nixos.caddy"
        fi
      '';
      after = [ "caddy:write-site" ];
      before = [ "devenv:enterShell" ];
    };

    "caddy:self-clean" = {
      description = "Remove caddy site files for deleted worktrees";
      exec = ''
        set -euo pipefail
        shopt -s nullglob 2>/dev/null || true
        for f in "${caddySitesDir}/"laravel-official-react-starter-kit-*.caddy; do
          base=$(basename "$f" .caddy)
          name=''${base#laravel-official-react-starter-kit-}
          [ "$name" = "main" ] && continue
          [ "$name" = "${worktreeName}" ] && continue
          if [ ! -d "${worktreesRoot}/$name" ]; then
            echo "→ removing stale caddy site: $name"
            rm -f "$f"
          fi
        done
      '';
      before = [ "devenv:enterShell" ];
    };

    # Fresh worktrees inherit primary's .env (which carries any real keys/
    # secrets your .env.example lacks). Falls back to .env.example if primary
    # has none. Devenv's `env = { ... }` block overrides URL/DB/Redis at the
    # shell-env layer via phpdotenv's immutable mode, so worktree-specific
    # values don't bleed in.
    "app:env-init" = {
      description = "Copy .env from primary worktree (or .env.example) into place";
      exec = ''
        set -euo pipefail
        primary_root="${worktreesRoot}/main"
        dst="${config.devenv.root}/.env"
        [ -f "$dst" ] && exit 0
        if [ -f "$primary_root/.env" ] && [ "$primary_root/.env" != "$dst" ]; then
          echo "→ Copying .env from primary worktree"
          cp "$primary_root/.env" "$dst"
        else
          echo "→ Copying .env from .env.example (primary has none)"
          cp "${config.devenv.root}/.env.example" "$dst"
        fi
      '';
      status = ''test -f "${config.devenv.root}/.env"'';
      before = [ "devenv:enterShell" ];
    };

    # Language deps — execIfModified tracks lockfile content hash in
    # .devenv/ SQLite, so a `git pull` that changes the lockfile triggers
    # a re-install on next shell entry.
    "deps:composer" = {
      description = "Install composer dependencies";
      cwd = config.devenv.root;
      exec = "composer install --no-interaction --prefer-dist";
      execIfModified = [
        "${config.devenv.root}/composer.lock"
        "${config.devenv.root}/composer.json"
      ];
      before = [ "devenv:enterShell" ];
      showOutput = true;
    };

    "deps:npm" = {
      description = "Install npm dependencies";
      cwd = config.devenv.root;
      exec = "npm ci";
      execIfModified = [
        "${config.devenv.root}/package-lock.json"
        "${config.devenv.root}/package.json"
      ];
      before = [ "devenv:enterShell" ];
      showOutput = true;
    };

    "app:key-generate" = {
      description = "Generate Laravel APP_KEY";
      cwd = config.devenv.root;
      exec = "php artisan key:generate --force --no-interaction";
      status = ''grep -qE '^APP_KEY=base64:' "${config.devenv.root}/.env"'';
      after = [
        "deps:composer"
        "app:env-init"
      ];
      before = [ "devenv:enterShell" ];
    };

    # Postgres roles are cluster-scoped (one shared `laravel_official_react_starter_kit` role) but
    # databases are per-worktree. We connect as $USER — the nix-darwin
    # postgres setup runs `initdb` without --username, so the cluster's
    # only superuser is the OS user (no `postgres` role exists). The app
    # itself connects as `laravel_official_react_starter_kit` over TCP using the env credentials.
    "db:ensure" = {
      description = "Create the worktree's Postgres role + database";
      exec = ''
        set -euo pipefail
        if ! psql -h 127.0.0.1 -U "$USER" -d postgres -tAc 'SELECT 1' >/dev/null 2>&1; then
          echo "⚠ Postgres not reachable on 127.0.0.1:5432 as $USER — is the nix-darwin daemon running?"
          exit 1
        fi

        # Shared role used by every worktree's app — created idempotently.
        # CREATEDB lets the role own per-worktree databases.
        if ! psql -h 127.0.0.1 -U "$USER" -d postgres -tAc \
             "SELECT 1 FROM pg_roles WHERE rolname='laravel_official_react_starter_kit'" | grep -q 1; then
          echo "→ Creating role laravel_official_react_starter_kit"
          psql -h 127.0.0.1 -U "$USER" -d postgres -c \
            "CREATE ROLE laravel_official_react_starter_kit WITH LOGIN PASSWORD 'laravel' CREATEDB"
        fi

        if ! psql -h 127.0.0.1 -U "$USER" -d postgres -tAc \
             "SELECT 1 FROM pg_database WHERE datname='${dbName}'" | grep -q 1; then
          echo "→ Creating database ${dbName}"
          createdb -h 127.0.0.1 -U "$USER" -O laravel_official_react_starter_kit ${dbName}
          mkdir -p "${config.devenv.root}/.devenv-state"
          touch "${config.devenv.root}/.devenv-state/needs-seed"
        fi
      '';
      status = ''psql -h 127.0.0.1 -U "$USER" -d ${dbName} -tAc 'SELECT 1' >/dev/null 2>&1'';
      before = [ "devenv:enterShell" ];
      showOutput = true;
    };

    # Idempotent (artisan checks the migrations table), so it runs every
    # entry but no-ops when nothing's pending.
    "db:migrate" = {
      description = "Run pending Laravel migrations";
      cwd = config.devenv.root;
      exec = "php artisan migrate --force";
      after = [
        "deps:composer"
        "app:key-generate"
        "db:ensure"
      ];
      before = [ "devenv:enterShell" ];
      showOutput = true;
    };

    "db:seed" = {
      description = "Seed the DB if it was just created";
      cwd = config.devenv.root;
      exec = ''
        set -euo pipefail
        php artisan db:seed --force
        rm -f "${config.devenv.root}/.devenv-state/needs-seed"
      '';
      # Skip (status=0) when marker is absent; run when marker exists.
      status = ''! test -f "${config.devenv.root}/.devenv-state/needs-seed"'';
      after = [ "db:migrate" ];
      before = [ "devenv:enterShell" ];
      showOutput = true;
    };

    "db:storage-link" = {
      description = "Create public/storage symlink";
      cwd = config.devenv.root;
      exec = "php artisan storage:link";
      status = ''test -L "${config.devenv.root}/public/storage"'';
      after = [
        "deps:composer"
        "app:env-init"
      ];
      before = [ "devenv:enterShell" ];
    };
  };

  # ─── enterTest: test suite ────────────────────────────────────────────
  # devenv:enterTest runs `after = [ "devenv:enterShell" ]` by default, so
  # every bootstrap task above has already completed (vendor/, .env, key,
  # DB, migrations) by the time we get here. Pass-through args:
  # `devenv test --filter=SomeTest`.
  enterTest = ''
    set -euo pipefail
    echo "→ Running test suite"
    php artisan test --parallel --recreate-databases "$@"
  '';

  # ─── enterShell: banner only (all bootstrap happens in tasks above) ────
  enterShell = ''
    figlet "Laravel"
    echo "── ${worktreeName} (index=${toString index}) ──"
    echo "  app             https://${appHost}"
    echo "  vite assets     https://${viteHost}"
    echo "  horizon ui      https://horizon.${appHost}"
    echo "  php-fpm         127.0.0.1:${toString phpPort}"
    echo "  vite            127.0.0.1:${toString vitePort}"
    echo "  db              ${dbName}"
    echo "  redis db        ${toString redisDbIndex}  (horizon prefix: horizon-${worktreeName}:)"
  '';
}
