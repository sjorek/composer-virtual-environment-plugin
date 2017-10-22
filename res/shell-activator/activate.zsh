#!@SHEBANG@
# This file must be used with "source @BIN_PATH@/activate.zsh" *from zsh*
# you cannot run it directly

if [ ! -z "${COMPOSER_VENV}" ] ; then
    echo ""
    echo "A virtual composer shell environment is already active!"
    echo ""
    echo "    Name: ${COMPOSER_VENV}"
    echo "    Path: ${COMPOSER_VENV_DIR}"
    echo ""
    echo "Run 'deactivate' before activating this environment."
    echo ""
    return 
fi

deactivate () {
    # This should detect zsh, which has a hash command that must
    # be called to get it to forget past commands.  Without forgetting
    # past commands the $PATH changes we made may not be respected
    if [ -n "$ZSH_VERSION" ] ; then
        hash -r
    fi

    if [ -d "${COMPOSER_VENV_DIR}/.composer-venv.d" ] ; then
        source <( cat "${COMPOSER_VENV_DIR}/.composer-venv.d/*.zsh" 2>/dev/null ) deactivate
    fi

    # reset old environment variables
    if [ -n "$_OLD_COMPOSER_VENV_PATH" ] ; then
        PATH="$_OLD_COMPOSER_VENV_PATH"
        export PATH
        unset _OLD_COMPOSER_VENV_PATH
    fi

    if [ -n "$_OLD_COMPOSER_VENV_PS1" ] ; then
        PS1="$_OLD_COMPOSER_VENV_PS1"
        export PS1
        unset _OLD_COMPOSER_VENV_PS1
    fi

    unset COMPOSER_VENV
    unset COMPOSER_VENV_DIR
    if [ ! "$1" = "nondestructive" ] ; then
        # Self destruct!
        unset -f deactivate

        echo ""
        echo "Left virtual composer shell environment."
        echo ""
        echo "Good Bye!"
        echo ""
    fi
}

# unset irrelevant variables
deactivate nondestructive

COMPOSER_VENV="@NAME@"
export COMPOSER_VENV

COMPOSER_VENV_DIR="@BASE_DIR@"
export COMPOSER_VENV_DIR

_OLD_COMPOSER_VENV_PATH="$PATH"
PATH="@BIN_DIR@:$PATH"
export PATH

if [ -z "$COMPOSER_VENV_DISABLE_PROMPT" ] ; then
    _OLD_COMPOSER_VENV_PS1="$PS1"
    if [ "x${COMPOSER_VENV}" != "x" ] ; then
        PS1="(${COMPOSER_VENV}) $PS1"
    elif [ "`basename \"${COMPOSER_VENV_DIR}\"`" = "__" ] ; then
        # special case for Aspen magic directories
        # see http://www.zetadev.com/software/aspen/
        PS1="[`basename \`dirname \"${COMPOSER_VENV_DIR}\"\``] $PS1"
    else
        PS1="(`basename \"${COMPOSER_VENV_DIR}\"`)$PS1"
    fi
    export PS1
fi

if [ -d "${COMPOSER_VENV_DIR}/.composer-venv.d" ] ; then
    source <( cat "${COMPOSER_VENV_DIR}/.composer-venv.d/*.zsh" 2>/dev/null ) activate
fi

# This should detect zsh, which has a hash command that must
# be called to get it to forget past commands.  Without forgetting
# past commands the $PATH changes we made may not be respected
if [ -n "$ZSH_VERSION" ] ; then
    hash -r
fi

echo ""
echo "virtual composer shell environment"
echo ""
echo "    Name: ${COMPOSER_VENV}"
echo "    Path: ${COMPOSER_VENV_DIR}"
echo ""
echo "Run 'deactivate' to exit the environment and return to normal shell."
echo ""
