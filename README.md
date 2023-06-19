# TMS (Tampere Multisite) WordPress Plugin Manual Events

## Installation

The boilerplate plugin works as a WordPress plugin straight out of the box. To customize the plugin for your needs, you need to replace all texts related to the boilerplate and write proper descriptions into various files.

### Replacements

To customize the plugin, do the following replacements:

- **tms-plugin-manual-events** - Replace with a suitable name for the plugin directory name. This is also used as the package name in package.json.
- **ManualEvents** - Replace with a suitable name for the plugin namespace, and the plugin class prefix. You must also refactor the plugin class filename for the autoloader to work.
- **manual_events()** - Replace with a global function name. This function returns the plugin singleton.
- **TMS Manual Events** - Replace with a proper plugin name to be displayed for your admin users.
- **TMS Manual Events** - Replace with a text describing your plugin.
- **tms-plugin-manual-events** - Replace with a text domain identifier.

Some texts are also related to [Geniem](https://www.geniem.com). Search and replace Geniem related texts if necessary.

## Contributing

Contributions are highly welcome! Just leave a pull request of your awesome well-written must-have feature.
