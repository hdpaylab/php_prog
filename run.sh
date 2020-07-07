#!/bin/sh

echo "Gathering mining info..."
php dump_mine.php
echo ""
echo ""

#echo "Whole Mining Statistics"
#echo ""
#php mine_stat.php mine.csv
#echo ""
#echo ""

echo "Open Mining Statistics"
echo ""
php mine_stat.php -o mine.csv
echo ""
echo ""

echo "Latest 24 Hour Block Statistics"
echo ""
php mine_stat.php -24 mine.csv
echo ""
echo ""
