# This file must be used with "source bin/activate" *from zsh*
# you cannot run it directly

deactivate () {
    # This should detect zsh, which has a hash command that must
    # be called to get it to forget past commands.  Without forgetting
    # past commands the $PATH changes we made may not be respected
    if [ -n "$ZSH_VERSION" ] ; then
        hash -r
    fi

    # reset old environment variables
    if [ -n "$_OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PATH" ] ; then
        PATH="$_OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PATH"
        export PATH
        unset _OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PATH
    fi

    if [ -n "$_OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PS1" ] ; then
        PS1="$_OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PS1"
        export PS1
        unset _OLD_COMPOSER_VIRTUAL_ENVIRONMENTL_PS1
    fi

    unset COMPOSER_VIRTUAL_ENVIRONMENT
    if [ ! "$1" = "nondestructive" ] ; then
    # Self destruct!
        unset -f deactivate
    fi
}

# unset irrelevant variables
deactivate nondestructive

COMPOSER_VIRTUAL_ENVIRONMENT="@BASE_DIR@"
export COMPOSER_VIRTUAL_ENVIRONMENT

_OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PATH="$PATH"
PATH="@BIN_DIR@:$PATH"
export PATH

if [ -z "$COMPOSER_VIRTUAL_ENVIRONMENT_DISABLE_PROMPT" ] ; then
    _OLD_COMPOSER_VIRTUAL_ENVIRONMENT_PS1="$PS1"
    if [ "x(@NAME@) " != x ] ; then
    PS1="(@NAME@) $PS1"
    else
    if [ "`basename \"$COMPOSER_VIRTUAL_ENVIRONMENT\"`" = "__" ] ; then
        # special case for Aspen magic directories
        # see http://www.zetadev.com/software/aspen/
        PS1="[`basename \`dirname \"$COMPOSER_VIRTUAL_ENVIRONMENT\"\``] $PS1"
    else
        PS1="(`basename \"$COMPOSER_VIRTUAL_ENVIRONMENT\"`)$PS1"
    fi
    fi
    export PS1
fi

# This should detect zsh, which has a hash command that must
# be called to get it to forget past commands.  Without forgetting
# past commands the $PATH changes we made may not be respected
if [ -n "$ZSH_VERSION" ] ; then
    hash -r
fi
