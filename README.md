The binary provides 3 options

1. `--sync`<br/>
   Synchronize origin repository and local repository.
   It will always try to create a new tag with the given version and update all the catpaw dependencies using your `prefix`.<br/>
   This means all your libraries should use the same prefix, in this case it would be `catpaw`, which translates to `"catpaw/<project>": "<version>"` for composer.<br/>
   This will also run `composer fix`, so make sure this composer script exists.
2.  `--export`<br/>
   Copy all `exports` (check the `product.yml` file) to all the other `projects`.<br/>
   Each project can overwrite the copied files using the `imports` property in `product.yml`.
3. `--delete-all-tags`<br/>
   Delete all repository local and remote tags, useful when tag naming becomes chaotic and needs a reset.

You __must__ specify a relative configuration `./product.yml` file.<br/>
An example of `./product.yml`:
```yaml

master: dev-tools

prefix: catpaw

exports:
  - "bin"
  - ".vscode"
  - ".github"
  - ".php-cs-fixer.php"
  - "psalm.xml"
  - "build.yml"

projects:
  dev-tools:
    version: 0.4.28
  core:
    version: 0.4.28
  web:
    version: 0.4.28
  cli:
    version: 0.4.28
  examples:
    version: 0.4.28
  mysql:
    version: 0.4.28
  mysql-dbms:
    version: 0.4.28
  optional:
    version: 0.4.28
  queue:
    version: 0.4.28
  raspberrypi:
    version: 0.4.28
  starter:
    version: 0.4.28
  store:
    version: 0.4.28
  cui:
    version: 0.4.28
  spa:
    version: 0.4.28
  web-starter:
    version: 0.4.28
  svelte-starter:
    version: 0.4.28
    imports:
      - "bin"
      - ".github"
      - ".php-cs-fixer.php"
      - "psalm.xml"
      - "build.yml"
  server-linking:
    version: 0.4.28
```

This configuration defines a master project called `dev-tools` located in a directory called `catpaw-dev-tools`.<br/>
The master project exports the following files to the other projects:

```yaml
exports:
  - "bin"
  - ".vscode"
  - ".github"
  - ".php-cs-fixer.php"
  - "psalm.xml"
  - "build.yml"
```

The `prefix` also applies to all the other projects, so this means that, for example, the `core` project is located in a directory called `catpaw-core`.

## Note
As you can see the `svelte-starter` specifies a property `imports`.<br/>
This  `imports` property overwrites the `export` property from the root of the configuration, making it possible to exclude or  inlcude certains fails into the  `svelte-start` project from the `dev-dools` master project.<br/>
In this case the outcome would be that `svelte-starter` will not import the `.vscode` directory.

## Usage examples

A few examples on how to use this binary.

### Delete all local and remote tags
```bash
php product.php ---delete-all-tags
```
This will delete all tags and releases.


### Export master files to the other projects
```bash
php product.php ---export
```
Will copy all fiels according the `exports` and `imports` definitions.

### Sync all repositories
```bash
php product.php ---sync
```
For each project this will...
1. run `composer fix`
1. commit, pull, push and push tags

Once all of the above operations have completed for all projects, it will also run `composer update` for each project.

It may be necessary to run this option multiple times in order to synchronize everything properly due to repository webhooks latency.