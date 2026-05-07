# TwigTrace Bundle

A Symfony bundle that automatically adds HTML comments to your rendered templates in development mode, making it easy to identify which Twig files, macros, and blocks are being used.

## Code Quality

[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/frvaillant/twig-trace-bundle/actions/workflows/code-quality.yml?query=branch:master)
[![PHP CS Fixer](https://img.shields.io/badge/PHP_CS_Fixer-fixed-brightgreen)](https://github.com/frvaillant/twig-trace-bundle/actions/workflows/code-quality.yml?query=branch:master)
[![Composer Audit](https://img.shields.io/badge/Composer_Audit-passed-brightgreen)](https://github.com/frvaillant/twig-trace-bundle/actions/workflows/code-quality.yml?query=branch:master+job:composer-audit)  

[View GitHub Actions](https://github.com/frvaillant/twig-trace-bundle/blob/master/.github/workflows/code-quality.yaml)


## Features

- **Automatic Template Tracing** - Every Twig template is wrapped with HTML comments showing its path  
- **Macro Detection** - Identifies which macros are being called and from which file  
- **Block Tracking** - Traces Twig blocks with customizable exclusions  
- **Fully Configurable** - Customize separators, excluded blocks, and excluded paths  
- **Debug Mode Only** - Only active in development, zero impact on production  
- **Zero Code Changes** - Works automatically without modifying your Twig templates

## Installation

Install the bundle via Composer:

```bash
composer require francoisvaillant/twig-trace
```

If you're not using Symfony Flex, manually register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Francoisvaillant\TwigTrace\TwigTraceBundle::class => ['all' => true],
];
```

## Usage

Once installed, the bundle automatically adds HTML comments to your rendered HTML in development mode:

```html
<!-- TEMPLATE: index.html.twig -->
<!-- TEMPLATE: base.html.twig -->
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="app.css">
    </head>
    <body>
<!-- BLOCK : index.html.twig::body -->
        <h1>Welcome 
<!-- MACRO: macros/user.html.twig::firstName -->
            <span class="user-firstname">John</span>
<!-- END MACRO: macros/user.html.twig::firstName -->
        </h1>
<!-- END TEMPLATE: home/index.html.twig -->
<!-- END BLOCK : index.html.twig::body -->
    </body>
</html>
<!-- END TEMPLATE: base.html.twig -->
<!-- END TEMPLATE: index.html.twig -->
```

## Configuration

If you want to customize default configuration, create a configuration file `config/packages/twig_trace.yaml`:

```yaml
twig_trace:
    # Custom separators for better visibility
    separator_template_start: '/////////////////'
    separator_template_end: '/////////////////'

  # Custom separators for better visibility
    separator_macro_start: '-------------'
    separator_macro_end: '-------------'

  # Custom separators for better visibility
    separator_block_start: '{{{{{{'
    separator_block_end: '}}}}}}'
    
    # Blocks to exclude from wrapping (useful for <title>, <meta>, etc.)
    excluded_blocks:
        - title
        - meta
        - stylesheets
        - javascripts

    # Macros to exclude from wrapping
    excluded_macros:
        - input
        - 'macros/forms.html.twig::textarea'
     
    # Template paths to exclude from tracing
    excluded_paths:
        - 'email/'           # Exclude email templates
        - 'pdf/'             # Exclude PDF templates
        - '@EasyAdmin'       # Exclude EasyAdmin templates
```

### Configuration Reference

| Option                     | Type | Default | Description                                                |
|----------------------------|------|---------|------------------------------------------------------------|
| `separator_template_start` | `string` | `''` | Separator displayed at the start of comments for templates |
| `separator_template_end`   | `string` | `''` | Separator displayed at the end of comments for templates   |
| `separator_macro_start`    | `string` | `''` | Separator displayed at the start of comments for macros    |
| `separator_macro_end`      | `string` | `''` | Separator displayed at the end of comments for macros      |
| `separator_block_start`    | `string` | `''` | Separator displayed at the start of comments for blocks    |
| `separator_block_end`      | `string` | `''` | Separator displayed at the end of comments for blocks      |
| `excluded_blocks`          | `array` | `['title', 'meta', 'stylesheets', 'javascripts']` | Block names to exclude from wrapping                       |
| `excluded_macros`          | `array` | `[]` | Macro names or exact `template::macro` pairs to exclude from wrapping |
| `excluded_paths`           | `array` | `[]` | Template paths to exclude (supports partial matches)       |

**Note:** `@WebProfiler` templates are always excluded automatically.

## How It Works

The bundle uses two mechanisms to trace your Twig templates:

1. **NodeVisitor** - Intercepts template rendering after compilation to add comments around complete templates
2. **LoaderDecorator** - Modifies the template source before compilation to wrap macros and blocks

This dual approach ensures:
- Templates with `{% extends %}` are handled correctly
- Macros are traced even when imported
- Blocks can be individually traced or excluded
- No conflicts with Twig's inheritance system

## Use Cases

### **Debugging Complex Template Hierarchies**
Quickly identify which child template is overriding a parent block.

### **Frontend Development**
Let your frontend developers know exactly which Twig file to edit.

### **Documentation**
Automatically document which templates are used on each page.

### **Testing**
Verify that the correct templates and macros are being rendered.

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Twig 3.0 or higher

## Production Safety

The bundle is **completely disabled in production** (`kernel.debug = false`). No HTML comments are added, and there is zero performance impact.

## Examples

### Custom Separators

```yaml
twig_trace:
    separator_template_start: '🔹'
    separator_template_end: '🔹'
```

Result:
```html
<!-- 🔹 BEGIN TEMPLATE: home/index.html.twig 🔹 -->
```

### Exclude Specific Directories

```yaml
twig_trace:
    excluded_paths:
        - 'admin/'
        - 'emails/'
        - '@SomeBundle'
```

### Exclude Blocks That Break Layout

Some blocks (like `title` or `meta`) should not contain HTML comments:

```yaml
twig_trace:
    excluded_blocks:
        - title
        - meta
        - head
```
Note that if you customize the excluded_blocks configuration, the default settings will not be applied anymore.  

### Exclude Specific Macros

Macros can be excluded globally by name or for one exact template:

```yaml
twig_trace:
    excluded_macros:
        - input
        - 'forms/macros.html.twig::textarea'
```

- `input` excludes that macro name everywhere
- `forms/macros.html.twig::textarea` excludes only that macro in that exact template

If you want to disable all tracing for one template file, use `excluded_paths` instead.

## Troubleshooting

### Comments not appearing?

1. Make sure you're in **debug mode** (`APP_ENV=dev`)
2. Clear the Twig cache: `php bin/console cache:clear`
3. Check your browser's "View Source" (not the Inspector, which may filter comments)

### "A template that extends another one cannot include content outside Twig blocks"

This error should not occur with TwigTrace. If you see it, please [open an issue](https://github.com/francoisvaillant/twig-trace/issues).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This bundle is released under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

Created by [François Vaillant](https://github.com/frvaillant)

## Support

- 🐛 [Report a bug](https://github.com/frvaillant/twig-trace-bundle/issues)
- 💡 [Request a feature](https://github.com/frvaillant/twig-trace-bundle/issues)

