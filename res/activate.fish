# This file must be used with ". bin/activate.fish" *from fish* (http://fishshell.org)
# you cannot run it directly

function deactivate  -d "Exit virtual environment and return to normal shell environment"
    # reset old environment variables
    if test -n "$_OLD_VIRTUAL_ENVIRONMENT_PATH"
        set -gx PATH $_OLD_VIRTUAL_ENVIRONMENT_PATH
        set -e _OLD_VIRTUAL_ENVIRONMENT_PATH
    end

    if test -n "$_OLD_FISH_PROMPT_OVERRIDE"
        functions -e fish_prompt
        set -e _OLD_FISH_PROMPT_OVERRIDE
        functions -c _old_fish_prompt fish_prompt
        functions -e _old_fish_prompt
    end

    set -e VIRTUAL_ENVIRONMENT
    if test "$argv[1]" != "nondestructive"
        # Self destruct!
        functions -e deactivate
    end
end

# unset irrelevant variables
deactivate nondestructive

set -gx VIRTUAL_ENVIRONMENT "@BASE_DIR@"

set -gx _OLD_VIRTUAL_ENVIRONMENT_PATH $PATH
set -gx PATH "$VIRTUAL_ENVIRONMENT/bin" $PATH

if test -z "$VIRTUAL_ENVIRONMENT_DISABLE_PROMPT"
    # fish uses a function instead of an env var to generate the prompt.

    # save the current fish_prompt function as the function _old_fish_prompt
    functions -c fish_prompt _old_fish_prompt

    # with the original prompt function renamed, we can override with our own.
    function fish_prompt
        # Save the return status of the last command
        set -l old_status $status

        # Prompt override?
        if test -n "(@NAME@) "
            printf "%s%s" "(@NAME@) " (set_color normal)
        else
            # ...Otherwise, prepend env
            set -l _checkbase (basename "$VIRTUAL_ENVIRONMENT")
            if test $_checkbase = "__"
                # special case for Aspen magic directories
                # see http://www.zetadev.com/software/aspen/
                printf "%s[%s]%s " (set_color -b blue white) (basename (dirname "$VIRTUAL_ENVIRONMENT")) (set_color normal)
            else
                printf "%s(%s)%s" (set_color -b blue white) (basename "$VIRTUAL_ENVIRONMENT") (set_color normal)
            end
        end

        # Restore the return status of the previous command.
        echo "exit $old_status" | .
        _old_fish_prompt
    end

    set -gx _OLD_FISH_PROMPT_OVERRIDE "$VIRTUAL_ENVIRONMENT"
end
