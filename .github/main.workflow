workflow "Run tests" {
  on = "push"
  resolves = ["Behaviour Codecov", "Check codestyle", "Static code analysis"]
}

action "Composer install" {
  uses = "MilesChou/composer-action@master"
  args = "install"
}

action "Behaviour tests" {
  needs = ["Composer install"]
  uses = "docker://php:7.2-alpine"
  runs = "phpdbg -qrr vendor/bin/behat --strict"
}

action "Behaviour test coverage" {
  needs = ["Behaviour tests"]
  uses = "docker://php:7.2-alpine"
  runs = "phpdbg -qrr vendor/bin/phpcov merge --clover=behat.xml coverage/default.cov"
}

action "Behaviour Codecov" {
  needs = ["Behaviour test coverage"]
  uses = "./.github/actions/codecov"
  args = "-F Behaviour -f behat.xml"
  secrets = ["CODECOV_TOKEN"]
}

action "Check codestyle" {
  needs = ["Composer install"]
  uses = "docker://php:7.2-alpine"
  runs = "vendor/bin/phpcs"
}

action "Static code analysis" {
  needs = ["Composer install"]
  uses = "docker://php:7.2-alpine"
  runs = "vendor/bin/phpstan analyse ."
}
