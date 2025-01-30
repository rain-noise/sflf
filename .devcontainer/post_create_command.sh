echo "============================================================"
echo " Set Up Dev Container ..."
echo "============================================================"
echo " Install php-cs-fixer"
echo "------------------------------------------------------------"
composer global require friendsofphp/php-cs-fixer:3.17.0 --dev
echo "> Done."

echo ""
echo "------------------------------------------------------------"
echo " Install Phpstan"
echo "------------------------------------------------------------"
composer global require phpstan/phpstan --dev
echo "> Done."

echo ""
echo "------------------------------------------------------------"
echo " Install Psysh"
echo "------------------------------------------------------------"
composer global require psy/psysh --dev
echo "> Done."

echo ""
echo "------------------------------------------------------------"
echo " Update global tools"
echo "------------------------------------------------------------"
composer global update --dev

echo ""
echo "------------------------------------------------------------"
echo " Install vondor modules"
echo "------------------------------------------------------------"
composer install
echo "> Done."

echo ""
echo "============================================================"
echo " Dev Container Was Ready"
echo "============================================================"
echo ""
