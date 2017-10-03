# This file must be used with "source bin/activate.csh" *from csh*.
# You cannot run it directly.
# Created by Davide Di Blasi <davidedb@gmail.com>.
# Ported to Python 3.3 venv by Andrew Svetlov <andrew.svetlov@gmail.com>

alias deactivate 'test $?_OLD_VIRTUAL_ENVIRONMENT_PATH != 0 && setenv PATH "$_OLD_VIRTUAL_ENVIRONMENT_PATH" && unset _OLD_VIRTUAL_ENVIRONMENT_PATH; rehash; test $?_OLD_VIRTUAL_ENVIRONMENT_PROMPT != 0 && set prompt="$_OLD_VIRTUAL_ENVIRONMENT_PROMPT" && unset _OLD_VIRTUAL_ENVIRONMENT_PROMPT; unsetenv VIRTUAL_ENVIRONMENT; test "\!:*" != "nondestructive" && unalias deactivate'

# Unset irrelevant variables.
deactivate nondestructive

setenv VIRTUAL_ENVIRONMENT "@BASE_DIR@"

set _OLD_VIRTUAL_ENVIRONMENT_PATH="$PATH"
setenv PATH "@BIN_DIR@:$PATH"


set _OLD_VIRTUAL_ENVIRONMENT_PROMPT="$prompt"

if (! "$?VIRTUAL_ENVIRONMENT_DISABLE_PROMPT") then
    if ("@NAME@" != "") then
        set env_name = "@NAME@"
    else
        if (`basename "VIRTUAL_ENVIRONMENT"` == "__") then
            # special case for Aspen magic directories
            # see http://www.zetadev.com/software/aspen/
            set env_name = `basename \`dirname "$VIRTUAL_ENVIRONMENT"\``
        else
            set env_name = `basename "$VIRTUAL_ENVIRONMENT"`
        endif
    endif
    set prompt = "[$env_name] $prompt"
    unset env_name
endif

#alias composer @COMPOSER_BIN@
#alias php @PHP_BIN@

rehash
