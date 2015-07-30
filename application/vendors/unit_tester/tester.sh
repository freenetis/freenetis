#!/bin/bash
################################################################################
# Script for triggering all test for FreenetIS.
#
# Author:  Ondřej Fibich
# Version: 1.2
#
# Test contains these steps:
#
#	(-) Login to FreenetIS
#	(1)	Syntax check of all files in FreenetIS
#	(2) Run config (re)generation
#	(3) Check models for errors
#	(4) Check helpers for errors
#	(5) Check controllers for errors
#
# Require:
#	curl, perl, libxml-writer-perl, libxml-dom-perl
#
################################################################################

function red_echo() {
	echo -e "\e[01;31m$1\e[0m"
}

function green_echo() {
	echo -e "\e[01;32m$1\e[0m"
}

# help
if [ "$1" == "--help" ]; then
	echo "Script for testing FreenetIS"
	echo "USAGE: tester.sh [options] URL username password"
	echo ""
	echo "Options:"
	echo " -o, --open-browser     Opens browser with models or helpers errors"
	echo "                        automatically, after error detection."
	echo " -s, --skip-syntax      Skip syntax check of all files"
	echo " -m, --skip-models      Skip test of models"
	echo " -h, --skip-helpers     Skip test of helpers"
	echo " -c, --skip-controllers Skip test of controllers"
	echo " -e, --enable-stats	  Turn on statistics and benchmarks"
	exit 0
fi

open_browser=
skip_syntax=
skip_models=
skip_helpers=
skip_controlles=
enable_stats=

# strip options
while [ $# -gt 3 ]; do
	case "$1" in
        --open-browser)
            open_browser=true
            ;;
        -o)
            open_browser=true
            ;;
        --skip-syntax)
            skip_syntax=true
            ;;
        -s)
            skip_syntax=true
            ;;
        --skip-models)
            skip_models=true
            ;;
        -m)
            skip_models=true
            ;;
        --skip-helpers)
            skip_helpers=true
            ;;
        -h)
            skip_helpers=true
            ;;
        --skip-controllers)
            skip_controlles=true
            ;;
        -c)
            skip_controlles=true
            ;;
        --enable-stats)
            enable_stats=true
            ;;
        -e)
            enable_stats=true
            ;;
        *)
            echo "Wrong arguments, see: tester.sh --help."
            exit 1
 
	esac
	shift
done

# check arguments
if [ $# -ne 3 ]; then
	echo "Wrong arguments, see: tester.sh --help."
	exit 1
fi

# check if unit test is enabled in config.php
cat ../../../config.php | grep \'unit_tester\' | grep -i TRUE 2>&1 1>/dev/null

if [ $? -ne 0 ]; then
	echo "Enable Unit Test by adding line into config.php: ";
	echo ""
	echo "\$config['unit_tester'] = TRUE;";
	echo ""
	exit 2
fi

url=$1
username=$2
password=$3

######### LOGIN TO FREENETIS ###################################################

echo "=== LOGIN TO FREENETIS ==="

if [ ! -d "curl" ]; then
	mkdir curl
fi

echo ""
echo "Getting cookies:"
echo ""

# Get test cookie
curl --cookie-jar curl/cookies_init.txt "${url}/en/login" > /dev/null

if [ $? -ne 0 ]; then
	echo ""
	red_echo "Can't connect to given URL, fill correct URL."
	echo ""
	exit 5
fi

echo ""
echo "Login to FreenetIS:"
echo ""

# Login
log_out=`curl --data "username=${username}&password=${password}&submit=Login" \
			  --cookie "curl/cookies_init.txt" \
			  --cookie-jar "curl/cookies_login.txt" \
			  "${url}/en/login"`

if [ $? -ne 0 ]; then
	echo ""
	red_echo "Can't login to given URL, fill correct URL."
	echo ""
	exit 5
fi

if [ "${log_out:0:8}" != "<a href=" ]; then
	echo ""
	red_echo "Can't login, wrong URL or login info."
	echo ""
	exit 6
fi


echo ""
green_echo "Logged in as  ${username}"
echo ""

######### STEP 1 -  Test syntax of all PHP files ###############################

if [ -z $skip_syntax ]; then

	echo "=== SYNTAX TESTING ==="

	# move to root directory
	cd ../../../
	# search all PHP files
	php_files_list=`find . -name "*.php"`
	count_php_files=`echo "${php_files_list}" | wc -l`
	counter_ok=0
	counter_error=0
		
	echo ""
	printf "%4d/%4d" 0 $count_php_files
	
	# iterate throught all files
	for php_file in $php_files_list
	do
		# test syntax error
		output=`php -l "${php_file}" 2>&1`
		
		echo -en "\b\b\b\b\b\b\b\b\b"
		printf "%4d/%4d" $counter_ok $count_php_files

		if [ $? -eq 0 ]; then
			let counter_ok++
		else
			let counter_error++
			echo ""
			echo "Syntax error in ${php_file}:"
			echo $output
		fi
	done

	# move back
	cd ./application/vendors/unit_tester

	# Info about check
	echo -en "\b\b\b\b\b\b\b\b\b"
	green_echo "${counter_ok} files has valid syntax"
	if [ $counter_error -ne 0 ]; then
		red_echo "${counter_error} files has invalid syntax"
	fi
	echo ""

	# Continue?
	if [ $counter_error -gt 0 ]; then
		echo "Test abort, fix syntax errors..."
		exit 1
	fi

fi

######### STEP 2 - Run config (re)generation ###################################

echo "=== (RE)GENERATING CONFIG ==="

perl utils/generate_unit_config.pl > generate_unit_config.log 2>&1

if [ $? -eq 0 ]; then
	echo ""
	green_echo "Config generated, see generate_unit_config.log for more details..."
	echo ""
else
	echo ""
	red_echo "Config generation failed, log written to generate_unit_config.log"
	echo ""
	echo "Test abort, see log..."
	exit 3
fi

######### STEP 3 - Check models for errors #####################################

if [ -z $skip_models ]; then

	echo "=== CHECKING MODELS ==="

	echo ""
	echo "Get unit tester results:"
	echo ""

	# Model test
	if [ -z $enable_stats ]; then
		curl --cookie "curl/cookies_login.txt" "${url}/en/unit_tester/models" > curl/models.html
	else
		curl --cookie "curl/cookies_login.txt" "${url}/en/unit_tester/models/enabled" > curl/models.html
	fi

	if [ $? -ne 0 ]; then
		echo ""
		red_echo "Can't connect to unit tester."
		echo ""
		exit 5
	fi

	# Test ouput
	output=`perl utils/result_info.pl "curl/models.html" 2>&1`

	if [ $? -ne 0 ]; then
		echo ""
		red_echo "Cannot validate ouput of test.\nError: ${output}"
		echo ""
		exit 6
	fi

	echo ""

	errors_count=`echo $output | cut -d: -f1`
	methods_count=`echo $output | cut -d: -f2`
	files_count=`echo $output | cut -d: -f3`

	if [ "$errors_count" != "0" ]; then
		red_echo "Error in model test $errors_count tests failed, see: curl/models.html"
		green_echo "$methods_count tests passed in $files_count models."

		if [ -n "$open_browser" ] ; then
			xdg-open curl/models.html 2>&1 1>/dev/null
		fi
	else
		if [ -n "$open_browser" ] && [ -n $enable_stats ] ; then
			xdg-open curl/models.html 2>&1 1>/dev/null
		fi

		green_echo "$methods_count tests passed in $files_count models."
	fi

	echo ""

fi

######### STEP 4 - Check helpers for errors ####################################

if [ -z $skip_helpers ]; then

	echo "=== CHECKING HELPERS ==="

	echo ""
	echo "Get unit tester results:"
	echo ""

	# Helper test
	curl --cookie "curl/cookies_login.txt" "${url}/en/unit_tester/helpers" > curl/helpers.html

	if [ $? -ne 0 ]; then
		echo ""
		red_echo "Can't connect to unit tester."
		echo ""
		exit 5
	fi

	# Test ouput
	output=`perl utils/result_info.pl "curl/helpers.html" 2>&1`

	if [ $? -ne 0 ]; then
		echo ""
		red_echo "Cannot validate ouput of test.\nError: ${output}"
		echo ""
		exit 6
	fi

	echo ""

	errors_count=`echo $output | cut -d: -f1`
	methods_count=`echo $output | cut -d: -f2`
	files_count=`echo $output | cut -d: -f3`

	if [ "$errors_count" != "0" ]; then
		red_echo "Error in helper test $errors_count tests failed, see: curl/helpers.html"
		green_echo "$methods_count tests passed in $files_count helpers."
		
		if [ -n "$open_browser" ] ; then
			xdg-open curl/helpers.html 2>&1 1>/dev/null
		fi
	else
		green_echo "$methods_count tests passed in $files_count helpers."
	fi

	echo ""

fi

######### STEP 5 - Check controllers for errors ################################

if [ -z $skip_controlles ]; then

	echo "=== CHECKING CONTROLLERS ==="

	echo ""
	echo "Wait please, this action can take several minutes..."
	echo ""

	rm -f curl/controller_* curl/cstats

	if [ -z $enable_stats ]; then
		output=`perl utils/controllers_test.pl "${url}" "curl/cookies_login.txt" 2>curl/tmp`
	else
		output=`perl utils/controllers_test.pl "${url}" "curl/cookies_login.txt" "stats_enabled" 2>curl/tmp`
	fi

	if [ "$?" != "0" ]; then
		red_echo "Failed to check controllers, error:"
		cat curl/tmp
		rm curl/tmp
		exit 7;
	fi

	errors_count=`echo $output | cut -d: -f1`
	valid_count=`echo $output | cut -d: -f2`

	if [ "$errors_count" != "0" ]; then
		green_echo "$valid_count tests passed in controllers."
		red_echo "$errors_count tests failed. Links to invalid tests:"

		echo ""
		
		if [ -n "$open_browser" ] ; then
			cat curl/tmp | while read url; do
				#split
				url_first=`echo $url | awk -F\: '{ print $1; }'`
				url_rest=`echo ${url#*:}`
				# print url
				echo $url_rest
				
				xdg-open "curl/controller_test_error_${url_first}.html" 2>&1 1>/dev/null
			done
		else
			cut -f 2 curl/tmp
		fi
	else
		green_echo "$valid_count tests passed in controllers."
	fi

	if [ -n "$enable_stats" ]; then
		echo ""
		green_echo "Benchmarks was written to curl/cstats"
		echo ""
	fi

	rm curl/tmp

fi

################################################################################

echo ""
echo "=============================="
echo ""
green_echo "Done!"
echo ""

exit 0