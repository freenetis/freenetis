#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Std;
use Data::Dumper;
use warnings;

if ($#ARGV != 3) {
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
my $i;

my $account = {"billingid" => $ARGV[2], "limit" => $ARGV[3]};

if ( $result = $lbilling->set_account_limit($account) ) {

   #print $result->{"billingid"}, ";";
   #print $result->{"from"}, ";";
   #print $result->{"to"}, "\n";

   Dumper($result);

   exit 1;
} else {
   my $error = $lbilling->get_error();
   my $errcount = @{$lbilling->get_error()};
   for ($i=0; $i<$errcount; $i++)
   {
      print $lbilling->get_error()->[$i], "\n";
   }
   exit 0;
}
