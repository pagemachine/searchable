source "node_modules/bash-require/index.sh"

require "shippable-tasks/typo3/extension"

task_fix_php56() {
  if [ "${SHIPPABLE_PHP_VERSION}" == "5.6" ]; then
    runner_run \
      composer remove --dev squizlabs/php_codesniffer slevomat/coding-standard
  fi
}
