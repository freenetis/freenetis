#!/usr/bin/perl
#
# Script get header of unit tester result
#
# @author Ondřej Fibich
# @version 1.0
#

if ($#ARGV != 0)
{
	die "Wrong args count: " . $#ARGV;
}

open(f, $ARGV[0]) || die "couldn't open the file!";
@lines = <f>;
close(f);

if ($lines[0] =~ /^<!-- (\d+):(\d+):(\d+) -->$/)
{
	print $1 . ":" . $2 . ":" . $3;
	exit(0);
}
else
{
	die "Wrong input!"
}

