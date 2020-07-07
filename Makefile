
bak:
	tar cf ~/BAK/php_prog-`date +"%y%m%d"`.tar *.php mine.csv *.sh README _ALL* *.cfg
	gzip -f ~/BAK/php_prog-`date +"%y%m%d"`.tar
