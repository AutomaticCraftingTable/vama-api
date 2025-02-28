### Local development
```
cp .env.example .env
make init
make run
```
Application will be running under [localhost:63851](localhost:63851) and [http://vama.localhost/](http://vama.localhost/) in traefik environment. If you don't have a traefik environment set up yet, follow the instructions from this [repository](https://github.com/AutomaticCraftingTable/traefik-environment).

#### Commands
Before running any of the commands below, you must run shell:
```
make shell
```

| Command                 | Task                                        |
|:------------------------|:--------------------------------------------|
| `composer <command>`    | Composer                                    |
| `composer test`         | Runs backend tests                          |
| `composer analyse`      | Runs Larastan analyse for backend files     |
| `composer cs`           | Lints backend files                         |
| `composer csf`          | Lints and fixes backend files               |
| `php artisan <command>` | Artisan commands                            |


#### Containers

| service    | container name            | default host port               |
|:-----------|:--------------------------|:--------------------------------|
| `app`      | `vama-app-dev`     | [63851](http://localhost:63851) |
| `database` | `vama-db-dev`      | 63853                           |
| `redis`    | `vama-redis-dev`   | 63852                           |
| `mailpit`  | `vama-mailpit-dev` | 63854                           |

### Docs
[db](https://github.com/AutomaticCraftingTable/vama-backend/doc/db.md)