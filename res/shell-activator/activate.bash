#!@SHEBANG@
# This file must be used with "source @BIN_PATH@/activate.bash" *from bash*
# you cannot run it directly

if [ -z "${COMPOSER_VENV_COLORS}" ] ; then
    COMPOSER_VENV_COLORS=@COLORS@
fi
export COMPOSER_VENV_COLORS

if [ -n "${COMPOSER_VENV_COLORS}" ] && [ ! "${COMPOSER_VENV_COLORS}" = "0" ] ; then
    _COMPOSER_VENV_getcolor () {
        local color ncolors bold underline standout normal black red green yellow blue magenta cyan white

        color=$1

        # see if bash supports colors...
        ncolors=@TPUT_COLORS@        # $(tput colors)

        if [ -n "$ncolors" ] && [ $ncolors -ge 8 ] ; then
            bold="@TPUT_BOLD@"       # "$(tput bold)"
            underline="@TPUT_SMUL@"  # "$(tput smul)"
            standout="@TPUT_SMSO@"   # "$(tput smso)"
            normal="@TPUT_SGR0@"     # "$(tput sgr0)"
            black="@TPUT_SETAF_0@"   # "$(tput setaf 0)"
            red="@TPUT_SETAF_1@"     # "$(tput setaf 1)"
            green="@TPUT_SETAF_2@"   # "$(tput setaf 2)"
            yellow="@TPUT_SETAF_3@"  # "$(tput setaf 3)"
            blue="@TPUT_SETAF_4@"    # "$(tput setaf 4)"
            magenta="@TPUT_SETAF_5@" # "$(tput setaf 5)"
            cyan="@TPUT_SETAF_6@"    # "$(tput setaf 6)"
            white="@TPUT_SETAF_7@"   # "$(tput setaf 7)"
            echo ${!color}
        fi
    }

else
    _COMPOSER_VENV_getcolor () {
        echo ""
    }
fi

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
    unset COMPOSER_VENV_COLORS

    return 
fi

deactivate () {
    # This should detect bash, which has a hash command that must
    # be called to get it to forget past commands.  Without forgetting
    # past commands the $PATH changes we made may not be respected
    if [ -n "$BASH_VERSION" ] ; then
        hash -r
    fi

    if [ -d "${COMPOSER_VENV_DIR}/.composer-venv.d" ] ; then
        source <( cat "${COMPOSER_VENV_DIR}/.composer-venv.d/*.bash" 2>/dev/null ) deactivate
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
    unset COMPOSER_VENV_COLORS
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

if [ -d "${COMPOSER_VENV_DIR}/.composer-venv.d" ] ; then
    source <( cat "${COMPOSER_VENV_DIR}/.composer-venv.d/*.bash" 2>/dev/null ) activate
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
