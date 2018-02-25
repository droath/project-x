# Project-X

[![Build Status](https://travis-ci.org/droath/project-x.svg?branch=master)](https://travis-ci.org/droath/project-x)


Project-X aims to cut down the time it takes for developers to setup a local development instance. It does this by automating most of the of the setup tasks, which can be executed via the CLI. 

Project-X is architected with a plugable concept from the ground up. This will allow developers to mold the build tool to fit the project requirements. The core of project-x is built on top of [Robo](https://robo.li/), which is modern task runner for PHP. Which allows for great flexibility and customization that we're able to leverage without having to reinvent the wheel.

## Project Types

We're only supporting Drupal project types at the moment. We have plans on supporting other popular PHP based projects, such as Laravel, Symfony and Wordpress. Among those you can create your own project type using the plugable nature of the application architecture. 

## Environment Engines

Docker is the environment engine of choice. There are plans to expand this support, but we're waiting to see where devopts is moving this year. Environment engines are plugable and can be expanded to meet the project's particular needs.
 
 ## Features
 
- Docker services support, allowing to swap out different services without writing a single line of code.
- Supports development built processes and deployment into a GitHub repo.
- Setup CI services, Probo CI and Travis CI in a flash.
- Integration with GitHub for improved developer workflow.
- Incorporate environment engines, and the idea of infrastructure configurations living along side the project.
- Setup projects based on pre-configured templates that are customizable.
- Setup testing tools like `behat` and `php-unit` with ease.
- Ensure that developers are following coding standards with code sniffer.
- Execute project specific commands before, or after a core command has ran.

Project-X tries to have a minimalistic approach on how the project is setup. The main goal is to keep the project configuration bloat to a minimum. If you don't need a particular feature don't add it until you truly need it, as all features can be added later on in the project.

## Getting Started

- `mkdir my-project && cd my-project`
- `composer init`
- `composer require --dev droath/project-x`
- `./vendor/bin/project-x init`
- `./vendor/bin/project-x project:setup`

## Configurations 

### Command Hooks

All Project-X commands have support for project specific commands to be ran before or after a core command has been executed. 

Add the following to your project-x.yml within the project root.

```yaml

command_hooks:
    project:
        up:
            after: 
                - echo 'Project is up!' 
            before:
                - echo 'Project is starting up!' 
        down:
            after: 
                 - echo 'Project is down!' 
                 - echo 'Do something else'
            before:
                 - echo 'Project is going down!' 
                 - { type: symfony, command: 'deploy:push' }
```
There is support to call project-x commands within the command hooks. Which is done by defining an object. The full syntax is the following:

```yaml
before:
  - { type: symfony, command: 'COMMAND_NAME', arguments: {'key': 'value'}, options: ['option'] }
```


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
