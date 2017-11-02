@echo off

if exists "@SHELL_HOOK_DIR@\pre-deactivate.bat" (
    call "@SHELL_HOOK_DIR@\pre-deactivate.bat"
)

if defined _OLD_COMPOSER_VENV_PROMPT (
    set "PROMPT=%_OLD_COMPOSER_VENV_PROMPT%"
)
set _OLD_COMPOSER_VENV_PROMPT=

if defined _OLD_COMPOSER_VENV_PATH (
    set "PATH=%_OLD_COMPOSER_VENV_PATH%"
)
set _OLD_COMPOSER_VENV_PATH=

set COMPOSER_VENV=
set COMPOSER_VENV_DIR=

echo ""
echo "Left virtual composer shell environment."
echo ""
echo "Good Bye!"
echo ""

if exists "@SHELL_HOOK_DIR@\post-deactivate.bat" (
    call "@SHELL_HOOK_DIR@\post-deactivate.bat"
)

:END
