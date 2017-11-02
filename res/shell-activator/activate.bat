@echo off

if defined COMPOSER_VENV (

    echo ""
    echo "A virtual composer shell environment is already active!"
    echo ""
    echo "    Name: %COMPOSER_VENV%"
    echo "    Path: %COMPOSER_VENV_DIR%"
    echo ""
    echo "Run 'call @VENDOR_DIR@\deactivate.bat' before activating this environment."
    echo ""

    goto END
)

if exists "@SHELL_HOOK_DIR@\pre-activate.bat" (
    call "@SHELL_HOOK_DIR@\pre-activate.bat"
)

set "COMPOSER_VENV=@NAME@"
set "COMPOSER_VENV_DIR=@BASE_DIR@"

if not defined PROMPT (
    set "PROMPT=$P$G"
)

if defined _OLD_COMPOSER_VENV_PROMPT (
    set "PROMPT=%_OLD_COMPOSER_VENV_PROMPT%"
)
set "_OLD_COMPOSER_VENV_PROMPT=%PROMPT%"
set "PROMPT=%COMPOSER_VENV%-%PROMPT%"

if defined _OLD_COMPOSER_VENV_PATH (
    set "PATH=%_OLD_COMPOSER_VENV_PATH%"
)
set "_OLD_COMPOSER_VENV_PATH=%PATH%"
set "PATH=@BIN_DIR@;%PATH%"

echo ""
echo "virtual composer shell environment"
echo ""
echo "    Name: %COMPOSER_VENV%"
echo "    Path: %COMPOSER_VENV_DIR%"
echo ""
echo "Run 'call @VENDOR_DIR@\deactivate.bat' to exit the environment."
echo ""

if exists "@SHELL_HOOK_DIR@\post-activate.bat" (
    call "@SHELL_HOOK_DIR@\post-activate.bat"
)

:END
