```
                             _ _
   _ __ ___   ___   ___   __| | | ___
  | '_ ` _ \ / _ \ / _ \ / _` | |/ _ \
  | | | | | | (_) | (_) | (_| | |  __/
  |_| |_| |_|\___/ \___/ \__,_|_|\___|
                      _          _
  __      _____  _ __| | ___ __ | | __ _  ___ ___
  \ \ /\ / / _ \| '__| |/ / '_ \| |/ _` |/ __/ _ \
   \ V  V / (_) | |  |   <| |_) | | (_| | (_|  __/
    \_/\_/ \___/|_|  |_|\_\ .__/|_|\__,_|\___\___|
                          |_|
```
Moodle Workplace is the official Moodle solution for organisational learning
and development. It has been developed in collaboration with key Moodle
Partners from around the world, pulling together best practices and services to
create a consistent and stable platform for organisational learning.

See <https://moodle.com/workplace/> for details of Moodle Workplace features.

## Setup for development

You can clone this repository using command below, which will also initialise
submodule dependencies and clone them in respective directories.

```
  git clone --recurse-submodules git@git.in.moodle.com:workplace/workplacedev.git
  cd workplacedev
  git submodule update --remote
```

The last command will pull upstream changes for all registered submodules (plugins).

### Upstream changes in workplace repo

If there are upstream changes in the branch, you need to pull the branch:

```
git pull
```

**After minor releases** the changes are force pushed so you need to hard reset:

```
git reset --hard origin/HEAD
```

If **new submodules were added** in the upstream repo:

* Make sure that the new plugins paths are not present in `.git/info/exclude`
* Run `git submodule update --init`

This will add all required plugins to your setup (do not worry if you already
have git clones of those plugins in respective directories, it will not affect
command run).

### Upstream changes in submodules (plugins)

You can pull upstream changes for all registered submodules (plugins) by
running:

```
git submodule update --remote
```

When you are pulling remote changes with `--remote` argument, by default this
does not overwrite your local changes in plugins, it just checks out the latest
mater commits in each plugin leaving it in headless mode.

### Development of plugins

In order to work on the plugin development, just `cd` to plugin directory and
treat it as independent git repo (you can switch branches, push upstream, this
will not affect main workplace repo in any way).

#### Pushing branches upstream

We are using a workflow where developer push the feature branch to `origin`
(plugin repo) and then creates MR, which triggers GitLab CI
for the branch.

Creating MRs can be done automatically if push options are passed. It is
possible to create git alias as shortcut to the list of push options:

```
git config --global alias.pushmr '!f() { git push -o merge_request.create -o merge_request.remove_source_branch -o merge_request.title="$1" origin HEAD; }; f'
```

It allows developer to push new branch (current branch to `origin`) and create
MR at the same time by running `git pushmr "This is MR title"` in the command line.


### Useful tips when working with submodules

1.  Commands `git diff` and `git status` on the main repository can be used with `--ignore-submodules`

2.  When you want to iterate over all submodules and run the same command on all of them you can use

    ```
    git submodule foreach COMMAND
    ```

    Note that if the command `COMMAND` exits with non-zero code on one of the submodules the looping stops.
    To make sure that we loop through all submodules no matter what one can use:

    ```
    git submodule foreach 'COMMAND ||:'
    ```

    inside the `COMMAND` you can use "special" variables such as `$name` and `$path`.

    More documentation on `git submodule`: https://git-scm.com/docs/git-submodule
