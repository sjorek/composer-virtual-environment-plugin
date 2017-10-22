#!@SHEBANG@
# This file must be used with "source @BIN_PATH@/activate.csh" *from csh*.
# You cannot run it directly.
# Created by Davide Di Blasi <davidedb@gmail.com>.
# Ported to Python 3.3 venv by Andrew Svetlov <andrew.svetlov@gmail.com>
# Adapted for composer by Stephan Jorek <stephan.jorek@gmail.com>

if ("$?COMPOSER_VENV") then
    goto skip 
endif

alias deactivate 'test $?_OLD_COMPOSER_VENV_PATH != 0 && setenv PATH "$_OLD_COMPOSER_VENV_PATH" && unset _OLD_COMPOSER_VENV_PATH; rehash; test $?_OLD_COMPOSER_VENV_PROMPT != 0 && set prompt="$_OLD_COMPOSER_VENV_PROMPT" && unset _OLD_COMPOSER_VENV_PROMPT; unsetenv COMPOSER_VENV; unsetenv COMPOSER_VENV_DIR; test "\!:*" != "nondestructive" && unalias deactivate && echo "" && echo "Left virtual composer shell environment." && echo "" && echo "Good Bye!" && echo ""'

# Unset irrelevant variables.
deactivate nondestructive

setenv COMPOSER_VENV "@NAME@"
setenv COMPOSER_VENV_DIR "@BASE_DIR@"

set _OLD_COMPOSER_VENV_PATH="$PATH"
setenv PATH "@BIN_DIR@:$PATH"


set _OLD_COMPOSER_VENV_PROMPT="$prompt"

if (! "$?COMPOSER_VENV_DISABLE_PROMPT") then
    if ("$COMPOSER_VENV" != "") then
        set env_name = "$COMPOSER_VENV"
    else
        if (`basename "$COMPOSER_VENV_DIR"` == "__") then
            # special case for Aspen magic directories
            # see http://www.zetadev.com/software/aspen/
            set env_name = `basename \`dirname "$COMPOSER_VENV_DIR"\``
        else
            set env_name = `basename "$COMPOSER_VENV_DIR"`
        endif
    endif
    set prompt = "[$env_name] $prompt"
    unset env_name
endif

rehash

echo ""
echo "virtual composer shell environment"
echo ""
echo "    Name: $COMPOSER_VENV"
echo "    Path: $COMPOSER_VENV_DIR"
echo ""
echo "Run 'deactivate' to exit the environment and return to normal shell."
echo ""
goto done


skip:
    echo ""
    echo "A virtual composer shell environment is already active!"
    echo ""
    echo "    Name: $COMPOSER_VENV"
    echo "    Path: $COMPOSER_VENV_DIR"
    echo ""
    echo "Run 'deactivate' before activating this environment."
    echo ""
    goto done

done:
    # nothing to do here
