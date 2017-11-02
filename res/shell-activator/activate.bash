#!@SHEBANG@
# This file must be used with 'source @BIN_PATH@/activate.bash' *from bash*
# you cannot run it directly

if [ -n "${COMPOSER_VENV:-}" ] ; then
    echo ''
    echo "Another $(_COMPOSER_VENV_style 'bold-underline-magenta')virtual composer shell environment$(_COMPOSER_VENV_style) is active!"
    echo ''
    echo "    Name: ${COMPOSER_VENV}"
    echo "    Path: ${COMPOSER_VENV_DIR}"
    echo ''
    echo "Run '$(_COMPOSER_VENV_style 'bold-yellow')deactivate$(_COMPOSER_VENV_style)' to leave it, before activating this environment."
    echo ''
    return
fi

COMPOSER_VENV_COLORS=${COMPOSER_VENV_COLORS:-@COLORS@}
export COMPOSER_VENV_COLORS

_COMPOSER_VENV_style () {
    local style ncolors bold underline standout normal black red green yellow blue magenta cyan white

    if [ -n "${COMPOSER_VENV_COLORS:-}" ] && [ ! "${COMPOSER_VENV_COLORS}" = '0' ] ; then

        # see if bash supports colors...
        ncolors=@TPUT_COLORS@        # $(tput colors)

        if [ -n "${ncolors:-}" ] && [ $ncolors -ge 8 ] ; then
            bold='@TPUT_BOLD@'       # "$(tput bold)"
            underline='@TPUT_SMUL@'  # "$(tput smul)"
            standout='@TPUT_SMSO@'   # "$(tput smso)"
            normal='@TPUT_SGR0@'     # "$(tput sgr0)"
            black='@TPUT_SETAF_0@'   # "$(tput setaf 0)"
            red='@TPUT_SETAF_1@'     # "$(tput setaf 1)"
            green='@TPUT_SETAF_2@'   # "$(tput setaf 2)"
            yellow='@TPUT_SETAF_3@'  # "$(tput setaf 3)"
            blue='@TPUT_SETAF_4@'    # "$(tput setaf 4)"
            magenta='@TPUT_SETAF_5@' # "$(tput setaf 5)"
            cyan='@TPUT_SETAF_6@'    # "$(tput setaf 6)"
            white='@TPUT_SETAF_7@'   # "$(tput setaf 7)"
            local IFS=$'-'
            for style in ${1:-normal} ; do
                echo -n ${!style}
            done
        fi
    fi
}

_COMPOSER_VENV_rehash () {
    # This should detect bash, which has a hash command that
    # must be called to get it to forget past commands. Without
    # forgetting past commands $PATH changes may not be respected.
    if [ -n "${BASH_VERSION:-}" ] || [ -n "${BASH:-}" ] ; then
        hash -r
    fi
}

_COMPOSER_VENV_hook () {
    if [ -n "${1:-}" ] && [ -d "@SHELL_HOOK_DIR@/${1}.d" ] ; then

        local hook oldpath filepath filename
        hook="$1"
        oldpath="$PATH"

        echo ''
        echo "Processing virtual environment $(_COMPOSER_VENV_style 'bold-green')${hook}$(_COMPOSER_VENV_style) shell-hooks"
        echo ''

        local IFS=$'\t\n'
        for filepath in "@SHELL_HOOK_DIR@/${hook}.d/"*.{sh,bash} ; do
            if [ ! -e "${filepath}" ] ; then
                continue
            fi
            filename=$( basename "$filepath" )
            echo "- shell-hook '$(_COMPOSER_VENV_style 'bold-yellow')${filename}$(_COMPOSER_VENV_style)': "
            if ! source "$filepath" ; then
                echo 'Failed to load shell-hook!' >&2
            fi
        done

        if [ ! "$PATH" = "$oldpath" ] ; then
            _COMPOSER_VENV_rehash
        fi
    fi
}

deactivate () {

    if [ ! "$1" = 'nondestructive' ] ; then
        _COMPOSER_VENV_hook 'pre-deactivate'
    fi

    # reset old environment variables
    if [ -n "${_OLD_COMPOSER_VENV_PATH:-}" ] ; then
        PATH="${_OLD_COMPOSER_VENV_PATH}"
        export PATH
        unset _OLD_COMPOSER_VENV_PATH
        _COMPOSER_VENV_rehash
    fi

    if [ -n "${_OLD_COMPOSER_VENV_PS1:-}" ] ; then
        PS1="${_OLD_COMPOSER_VENV_PS1}"
        export PS1
        unset _OLD_COMPOSER_VENV_PS1
    fi

    if [ -n "${COMPOSER_VENV:-}" ] ; then
        unset COMPOSER_VENV
    fi

    if [ -n "${COMPOSER_VENV_DIR:-}" ] ; then
        unset COMPOSER_VENV_DIR
    fi

    if [ -n "${COMPOSER_VENV_COLORS:-}" ] ; then
        unset COMPOSER_VENV_COLORS
    fi

    if [ ! "$1" = 'nondestructive' ] ; then

        echo ''
        echo 'Left virtual composer shell environment.'
        echo ''
        echo 'Good Bye!'
        echo ''

        _COMPOSER_VENV_hook 'post-deactivate'

        # Self destruct!
        unset -f deactivate
        unset -f _COMPOSER_VENV_hook
        unset -f _COMPOSER_VENV_rehash
        unset -f _COMPOSER_VENV_style
    fi
}

# unset irrelevant variables
deactivate 'nondestructive'

_COMPOSER_VENV_hook 'pre-activate'

COMPOSER_VENV='@NAME@'
export COMPOSER_VENV

COMPOSER_VENV_DIR='@BASE_DIR@'
export COMPOSER_VENV_DIR

_OLD_COMPOSER_VENV_PATH="$PATH"
PATH="@BIN_DIR@:$PATH"
export PATH

_COMPOSER_VENV_rehash

if [ -z "${COMPOSER_VENV_DISABLE_PROMPT:-}" ] ; then
    _OLD_COMPOSER_VENV_PS1="$PS1"
    if [ "x${COMPOSER_VENV}" != 'x' ] ; then
        PS1="$(_COMPOSER_VENV_style 'bold-green')(${COMPOSER_VENV})$(_COMPOSER_VENV_style) $PS1"
    elif [ "`basename \"${COMPOSER_VENV_DIR}\"`" = '__' ] ; then
        # special case for Aspen magic directories
        # see http://www.zetadev.com/software/aspen/
        PS1="[`basename \`dirname \"${COMPOSER_VENV_DIR}\"\``] $PS1"
    else
        PS1="(`basename \"${COMPOSER_VENV_DIR}\"`)$PS1"
    fi
    export PS1
fi

echo ''
echo "$(_COMPOSER_VENV_style 'bold-underline-magenta')virtual composer shell environment$(_COMPOSER_VENV_style)"
echo ''
echo "    Name: $(_COMPOSER_VENV_style 'bold-green')${COMPOSER_VENV}$(_COMPOSER_VENV_style)"
echo "    Path: $(_COMPOSER_VENV_style 'bold-green')${COMPOSER_VENV_DIR}$(_COMPOSER_VENV_style)"
echo ''
echo "Run '$(_COMPOSER_VENV_style 'bold-yellow')deactivate$(_COMPOSER_VENV_style)' to exit the environment and return to normal shell."
echo ''

_COMPOSER_VENV_hook 'post-activate'
