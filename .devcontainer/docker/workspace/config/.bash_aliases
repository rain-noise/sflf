# =========================================================
# Here is an alias that is convenient for development.
# =========================================================
# Show/Edit/Source this file
# ---------------------------------------------------------
alias bacat="cat ~/.bash_aliases"
alias bavim="vim ~/.bash_aliases"
alias basrc="source ~/.bash_aliases"

# ---------------------------------------------------------
# Utility aliases
# ---------------------------------------------------------
alias ll='ls -l'
alias la='ls -A'

# ---------------------------------------------------------
# Add Composer global vendor bin directory to PATH
# ---------------------------------------------------------
export PATH=$PATH:~/.composer/vendor/bin

# ---------------------------------------------------------
# php-cs-fixer
# ---------------------------------------------------------
# alias php-cs-fixer="~/.composer/vendor/bin/php-cs-fixer"
alias pcf="php-cs-fixer"
alias pcf-f="pcf fix --config=/workspace/.php-cs-fixer.dist.php"

# ---------------------------------------------------------
# phpstan
# ---------------------------------------------------------
# alias phpstan="~/.composer/vendor/bin/phpstan"
alias phpstan-a="phpstan analyze -c /workspace/phpstan.neon"

# ---------------------------------------------------------
# psysh
# ---------------------------------------------------------
# alias psysh="~/.composer/vendor/bin/psysh"
alias psysh-app="psysh /workspace/.devcontainer/docker/workspace/config/psysh_autoload.php"

# ---------------------------------------------------------
# phpunit
# ---------------------------------------------------------
alias phpunit="php -d memory_limit=256M /workspace/src/main/php/vendor/bin/phpunit"
