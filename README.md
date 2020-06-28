# Deployment instructions
0. Download id_rsa.sseriga* certificates from cPanel to your local .ssh directory
1. You may have to set 0400 permissions ```chmod 0400 ~/.ssh/id_rsa.sseriga*```
2. Add an additional git remote ssh://mediacen@mediacentre.sseriga.edu:/home2/mediacen/mediacentre/.git
3. Merge into production/master to update remote files
4. ssh into mediacen@mediacentre.sseriga.edu
5. Drupal is located in /home2/mediacen

# Setup instructions
0. Before starting to do something, pray that you will never ever meet Drupal famous "White screens of death"
1. Download drupal
```
git pull master
```
2. Install libraries
```
composer install
```
3. Configure apache2/nginx, set root directory to "web/"
4. Set PHP version to 7.1, pray again
5. Create a MySQL DB
6. Copy config file
```
cp web/sites/example.settings.local.php web/sites/default/settings.local.php
```
7. Configure the file web/sites/default/settings.local.php
8. Open site in browser - install standard

# Development
Always use phpcs for code quality checks on `modules/custom/` and `themes/custom/` directories, e.g. from `web/` directory:
```bash
../vendor/bin/phpcs --standard=core/phpcs.xml.dist modules/custom/ themes/custom/
```

Always use phpcbf for code fixing on `modules/custom` and `themes/custom/` directories, e.g. from `web/` directory:
```bash
../vendor/bin/phpcbf --standard=core/phpcs.xml.dist modules/custom/ themes/custom/
```

# CKeditor overrides
Add following fragment in `web/modules/custom/content_page/content_page.module` (not really the right place, but does what we need)

```
/**
 * Implements hook_editor_js_settings_alter
 */
function content_page_editor_js_settings_alter(array &$settings) {
  foreach ($settings['editor']['formats'] as $name => $value) {
    $settings['editor']['formats'][$name]['editorSettings']['font_names'] = 'Arial;Verdana;';
  }
}
```
