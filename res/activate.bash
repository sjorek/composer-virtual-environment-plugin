#!bash
# This file must be used with "source bin/activate" *from bash*
# you cannot run it directly

_COMPOSER_VENV_getcolor () {
    local color ncolors bold underline standout normal black red green yellow blue magenta cyan white

    color=$1
    bold=""
    underline=""
    standout=""
    normal=""
    black=""
    red=""
    green=""
    yellow=""
    blue=""
    magenta=""
    cyan=""
    white=""

    # see if it supports colors...
    ncolors=$(tput colors)

    if [ -z "$COMPOSER_VENV_DISABLE_COLOR_PROMPT" ] && [ -n "$ncolors" ] && [ $ncolors -ge 8 ] ; then
        bold="$(tput bold)"
        underline="$(tput smul)"
        standout="$(tput smso)"
        normal="$(tput sgr0)"
        black="$(tput setaf 0)"
        red="$(tput setaf 1)"
        green="$(tput setaf 2)"
        yellow="$(tput setaf 3)"
        blue="$(tput setaf 4)"
        magenta="$(tput setaf 5)"
        cyan="$(tput setaf 6)"
        white="$(tput setaf 7)"
    fi
    echo ${!color}
}

if [ ! -z "${COMPOSER_VENV}" ] ; then
    echo ""
    echo "$(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'underline')$(_COMPOSER_VENV_getcolor 'red')A virtual composer shell environment is already active!$(_COMPOSER_VENV_getcolor 'normal')"
    echo ""
    echo "    Name: $(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'green')${COMPOSER_VENV}$(_COMPOSER_VENV_getcolor 'normal')"
    echo "    Path: $(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'green')${COMPOSER_VENV_DIR}$(_COMPOSER_VENV_getcolor 'normal')"
    echo ""
    echo "Run '$(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'yellow')deactivate$(_COMPOSER_VENV_getcolor 'normal')' before activating this environment."
    echo ""

    # remove color helper
    unset -f _COMPOSER_VENV_getcolor

    return 
fi

deactivate () {
    # This should detect bash, which has a hash command that must
    # be called to get it to forget past commands.  Without forgetting
    # past commands the $PATH changes we made may not be respected
    if [ -n "$BASH_VERSION" ] ; then
        hash -r
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
        PS1="$(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'green')(${COMPOSER_VENV})$(_COMPOSER_VENV_getcolor 'normal') $PS1"
    elif [ "`basename \"${COMPOSER_VENV_DIR}\"`" = "__" ] ; then
        # special case for Aspen magic directories
        # see http://www.zetadev.com/software/aspen/
        PS1="[`basename \`dirname \"${COMPOSER_VENV_DIR}\"\``] $PS1"
    else
        PS1="(`basename \"${COMPOSER_VENV_DIR}\"`)$PS1"
    fi
    export PS1
fi

# This should detect bash, which has a hash command that must
# be called to get it to forget past commands.  Without forgetting
# past commands the $PATH changes we made may not be respected
if [ -n "$BASH_VERSION" ] ; then
    hash -r
fi

echo ""
echo "$(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'underline')$(_COMPOSER_VENV_getcolor 'magenta')virtual composer shell environment$(_COMPOSER_VENV_getcolor 'normal')"
echo ""
echo "    Name: $(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'green')${COMPOSER_VENV}$(_COMPOSER_VENV_getcolor 'normal')"
echo "    Path: $(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'green')${COMPOSER_VENV_DIR}$(_COMPOSER_VENV_getcolor 'normal')"
echo ""
echo "Run '$(_COMPOSER_VENV_getcolor 'bold')$(_COMPOSER_VENV_getcolor 'yellow')deactivate$(_COMPOSER_VENV_getcolor 'normal')' to exit the environment and return to normal shell."
echo ""

# remove color helper
unset -f _COMPOSER_VENV_getcolor
