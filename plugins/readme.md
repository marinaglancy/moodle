For each Moodle plugin create a subdirectory with the name of the full plugin name, for example

- "blocks/example" will become "plugins/block_example",
- "admin/tool/example" will become "plugins/tool_example", etc.

In order for the plugins to work in both locations:

Include config.php only if it was not already included:

```
defined('MOODLE_INTERNAL') || require('../../config.php');
```

Instead of `require_once()` and `include_once()` for the plugin files use:

```
core_component::require_plugin_file('blocks/example/lib.php');
core_component::require_plugin_file('admin/tool/example/lib.php');
```

Instead of `require()` and `include()` for the plugin files use the same
function with the second argument `false`:

```
core_component::require_plugin_file('blocks/example/version.php', false);
core_component::require_plugin_file('admin/tool/example/version.php', false);
```
