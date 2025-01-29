# Composer Plugin to patch PHP Project Dependencies in multi-repository CI/CD pipelines

[![Latest Stable Version](https://poser.pugx.org/hiqdev/composer-ci-deps-plugin/v)](https://packagist.org/packages/hiqdev/composer-ci-deps-plugin)
[![Total Downloads](https://poser.pugx.org/hiqdev/composer-ci-deps-plugin/downloads)](https://packagist.org/packages/hiqdev/composer-ci-deps-plugin)

This plugin is designed for CI/CD pipelines of a multi-repository project.
When a project consists of multiple repositories, it is often necessary to test
the changes in the dependencies of the project before merging them.
This plugin helps to automate this process.

It allows to automatically patch the dependencies of the project based on the list
of pull requests that are currentto be used inly being tested.

## Limitations

1. Currently, the plugin supports pull requests from:
   1. Public GitHub repositories
   2. Private GitLab repositories
2. Only pull requests against the installed dependency versions are supported.
   It means that the plugin will not work if the pull request is against the
   feature branch, and you have the main repo branch installed as a dependency.
3. If you already use [cweagans/composer-patches](https://github.com/cweagans/composer-patches),
   in your project, you should install this plugin with caution, as it was not tested
   in such an environment and may cause conflicts.

## Supported environment variables

| Name                       | Description                                                                                                                   | Required? |
|----------------------------|-------------------------------------------------------------------------------------------------------------------------------|-----------|
| `GITLAB_REPO_ACCESS_TOKEN` | Gitlab Access Token with **read_api**, **read_repo** grants.<br />Make sure the token owner has access to the required repos. | Required  |

## Installation

```bash
composer require --dev hiqdev/composer-ci-deps-plugin
```

## Usage

1. Create a `pull-requests.txt` in the root of your project with the list of pull requests,
   one per line, for example:
    ```txt
    https://gitlab.com/ultimateretro/ultimateretro/-/merge_requests/49
    https://github.com/hiqdev/php-billing/pull/94
    ```
   The plugin will automatically detect the type of the pull requests.

2. Run `composer prp` – this will download patches from the pull requests and apply them to the dependencies.


## Development

The easiest way to test the plugin is to create a new project and require the plugin from the local path:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/composer-ci-deps-plugin"
        }
    ],
    "require-dev": {
        "hiqdev/composer-ci-deps-plugin": "*"
    }
}
```

Then you can run the plugin with the following command and enjoy the debugging with XDebug:

```bash
export XDEBUG_TRIGGER=1
export COMPOSER_ALLOW_XDEBUG=1
export GITLAB_REPO_ACCESS_TOKEN=<your GitLab access token>
composer prp
```
