# =========================================================
# Here is an alias that is convenient for development.
# =========================================================
# Show/Edit/Source this file
# ---------------------------------------------------------
alias ba-show="cat ~/.bash_aliases | egrep '^(alias|function)'"
alias ba-edit="vim ~/.bash_aliases"
alias ba-load="source ~/.bash_aliases"

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
alias psysh-app="psysh /workspace/src/main/php/vendor/autoload.php"

# ---------------------------------------------------------
# phpunit
# ---------------------------------------------------------
alias phpunit="php -d memory_limit=256M /workspace/src/main/php/vendor/bin/phpunit"
alias phpunit-all="phpunit /workspace/tests/"
