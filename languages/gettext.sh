# Change every instance of pmpro-bbpress below to match your actual plugin slug
#---------------------------
# This script generates a new pmpro.pot file for use in translations.
# To generate a new pmpro-bbpress.pot, cd to the main /pmpro-bbpress/ directory,
# then execute `languages/gettext.sh` from the command line.
# then fix the header info (helps to have the old pmpro.pot open before running script above)
# then execute `cp languages/pmpro-bbpress.pot languages/pmpro-bbpress.po` to copy the .pot to .po
# then execute `msgfmt languages/pmpro-bbpress.po --output-file languages/pmpro-bbpress.mo` to generate the .mo
#---------------------------
echo "Updating pmpro-bbpress.pot... "
xgettext -j -o languages/pmpro-bbpress.pot \
--default-domain=pmpro-bbpress \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--sort-by-file \
--package-version=1.1 \
--msgid-bugs-address="info@paidmembershipspro.com" \
$(find . -name "*.php")
echo "Done!"