#!@SHEBANG@
# This file must be used with 'source @BIN_PATH@/activate.zsh' *from zsh*
# you cannot run it directly

if [ -n "${COMPOSER_VENV:-}" ] ; then
    echo ''
    echo 'Another virtual composer shell environment is active!'
    echo ''
    echo "    Name: ${COMPOSER_VENV}"
    echo "    Path: ${COMPOSER_VENV_DIR}"
    echo ''
    echo "Run 'deactivate' to leave it, before activating this environment."
    echo ''
    return 
fi

_COMPOSER_VENV_rehash () {
    # This should detect zsh, which has a hash command that
    # must be called to get it to forget past commands. Without
    # forgetting past commands $PATH changes may not be respected.
    if [ -n "${ZSH_VERSION:-}" ] ; then
        hash -r
    fi
}

_COMPOSER_VENV_hook () {
    if [ -n "${1:-}" ] && [ -d "@SHELL_HOOK_DIR@/${1}.d" ] ; then

        local hook oldpath oldopts filepath filename
        hook="$1"
        oldpath="$PATH"

        echo ''
        echo "Processing virtual environment ${hook} shell-hooks"
        echo ''

        oldopts=$(setopt)
        setopt -o nullglob
        local IFS=$'\t\n'
        for filepath in "@SHELL_HOOK_DIR@/${hook}.d/"*.{sh,zsh} ; do
            setopt +o nullglob
            setopt -o $( echo $oldopts )
            filename=$( basename "$filepath" )
            echo "- shell-hook '${filename}'"
            if ! source "$filepath" ; then
                echo 'Failed to load shell-hook!' >&2
            fi
            oldopts=$(setopt)
        done
        setopt +o nullglob
        setopt -o $( echo $o )

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
        PS1="(${COMPOSER_VENV}) $PS1"
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
echo 'virtual composer shell environment'
echo ''
echo "    Name: ${COMPOSER_VENV}"
echo "    Path: ${COMPOSER_VENV_DIR}"
echo ''
echo 'Run 'deactivate' to exit the environment and return to normal shell.'
echo ''

_COMPOSER_VENV_hook 'post-activate'