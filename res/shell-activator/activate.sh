#!@SHEBANG@
# This file must be used with 'source @BIN_PATH@/activate.sh' *from bash or zsh*
# you cannot run it directly

if [ -n "${BASH_VERSION:-}" ] || [ -n "${BASH:-}" ] ; then
    source '@BIN_DIR@/activate.bash'
elif [ -n "${ZSH_VERSION:-}" ] ; then
    source '@BIN_DIR@/activate.zsh'
else
    echo 'Could not determine shell version.'
    echo 'Virtual environment not activated.'
fi
