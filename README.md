Img
===
Img is a port of [ImageWorkshop](http://phpimageworkshop.com/) for ExpressionEngine.

http://phpimageworkshop.com/
https://github.com/Sybio/ImageWorkshop

### Installation

1. Install addon
2. Add this to your config:

```php
$env_config['wda_img_prefix'] = 'img:';
$env_config['wda_img_webroot_path'] = APPPATH.'../../webroot/';
$env_config['wda_img_cache_path'] = $env_config['wda_img_webroot_path'].'cache';
$env_config['wda_img_cache_url'] = $env_config['base_url'].'cache';
$env_config['wda_img_debug'] = 'y'; // y will throw error messages on failure
$env_config['wda_img_disable_cache'] = 'n'; // y will disable cache (new image will always be generated and overwrite existing file in cache)
$env_config['wda_img_do_not_resize_if_match'] = 'y';    // do not resize if image is exact match
```

### Tags

Example usage w/an Assets field:

```html
{exp:img:resize src="{portfolio_grid_image:server_path}" width="235" height="235" retina="yes"}
<img src="{img:url}" width="100%" height="100%"/>
{/exp:img:resize}
```

Note: retina="yes" will produce width/height * 2 in this case a 470x470 image.

Example usage w/a normal File field

```html
{exp:img:resize src="{portfolio_grid_image}" width="235" height="235" retina="yes"}
<img src="{img:url}" width="100%" height="100%"/>
{/exp:img:resize}
```
