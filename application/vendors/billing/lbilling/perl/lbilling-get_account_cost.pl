#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 1) {
  print "Wrong number of parameters", "\n";
  exit 0;
}

use SOAP::Lite +autodispatch =>
   uri => "http://sip.nfx.czf/lBilling",
   proxy => 'https://'.$ARGV[0].':'.$ARGV[1].'@sip.nfx.czf/cgi-bin/admin/lbilling/soap.pl';

my $lbilling = lBilling->new();

unless ( $lbilling ) {
  print "Could not create SOAP instance", "\n";
  exit 0;
}

my $result;
my $line;
my @error;

my $account = {"billingid" => 1949, "from" => 0, "to" => time()};

if ( $result = $lbilling->get_account_cost($account) ) {
   #print $result->{"valid_to"}, "\n";
   #print $result->{"valid_from"}, "\n";
   #print $result->{"descr"}, "\n";
   #print $result->{"state"}, "\n";
   #print $result->{"billingid"}, "\n";
   #print $result->{"currency"}, "\n";
   #print $result->{"ballance"}, "\n";
   #print $result->{"limit"}, "\n";
   #print $result->{"type"}, "\n";
   #print $result->{"partner"}, "\n";
   print Dumper($result);
   exit 1;
} else {
    @error = $lbilling->get_error();
	print Dumper($lbilling->get_error());
    #print $error[0][0], "\n";
    #print $error[0][1], "\n";
    exit 0;
}
