# Project-X

[![Build Status](https://travis-ci.org/droath/project-x.svg?branch=master)](https://travis-ci.org/droath/project-x)

### About
When starting a new project, there are many repetive set up tasks that must be done before you're ready to start development. This tool aims to cut down on startup time by automating those setup tasks for you through `cli` based prompts. It is architected to be pluggable, allowing for extension of the tool to fit your needs.

`project-x` provides frameworks for the following:
- Setting up new projects based on pre-configured templates
- Incorporating engines (aka local development environments) into your code base in order to share them amongst your development team
- Integrating with hosted version control services like github
- Setting up testing utilties like `behat` and `php-unit`
- Setting up continuous integratin servers like Probo CI and Travis CI

`project-x` allows you to choose which of these features you use on your project. If you don't want a particular feature, just say no to the prompt when the time comes. If you decide later that you want to start using a particular feature that wasn't originally configured, you can run through the setup process again and add it in.

### Getting Started

- `mkdir my-project && cd my-project`
- `composer init`
- `composer require --dev droath/project-x`
- `./vendor/bin/project-x init`
- `./vendor/bin/project-x project:setup`

### Bash alias
If you would like to avoid typing `./vendor/bin/project-x` every time you use `project-x`, you may add the following to your `bash_profile`:

```bash
function project-x()
{
  if [ "`git rev-parse --show-cdup 2> /dev/null`" != "" ]; then
    GIT_ROOT=$(git rev-parse --show-cdup)
  else
    GIT_ROOT="."
  fi

  if [ -f "$GIT_ROOT/vendor/bin/project-x" ]; then
    $GIT_ROOT/vendor/bin/project-x "$@"
  elif [ -f "$GIT_ROOT/../vendor/bin/project-x" ]; then
    $GIT_ROOT/../vendor/bin/project-x "$@"
  else
    echo "You must run this command from within a Project-X project."
    return 1
  fi
}
```
