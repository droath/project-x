# Drush configuration and aliases

The `drush` directory is intended to contain drush configuration that is not site or environment specific.

Site specific drush configuration lives in `sites/[site-name]`.

## Site aliases

### For remote environments

You should add the Drush aliases for any remote environments to the `site-aliases` directory. This allows developers to access remote environments using simple aliases such as `drush @mysite.dev uli`.

Note that if the version of Drush that your project uses is significantly ahead of the version available in the remote environment, you’ll need to manually set `$drush_major_version` at the top of your alias files to match the version of Drush on the remote environment.

### For local environment

It can be helpful to define aliases for a local environment such as `@local.mysite`. This creates consistency with how aliases are already defined for remote environments (such as `@mysite.dev`, `@mysite.test`, and `@mysite.prod`). To create these local aliases, copy `local.aliases.example.drushrc.php` to `local.aliases.drushrc.php` and modify the default values as appropriate for your project.
