<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("bills");
echo '<br>';
if ($num > 1) print_titre($langs->trans("RelatedBills"));
else print_titre($langs->trans("RelatedBill"));
?>
<table class="noborder allwidth">
<tr class="liste_titre">
	<td><?php echo $langs->trans("Ref"); ?></td>
	<td align="center"><?php echo $langs->trans("Date"); ?></td>
	<td align="right"><?php echo $langs->trans("AmountHTShort"); ?></td>
	<td align="right"><?php echo $langs->trans("Status"); ?></td>
</tr>
<?php
$var=true;
$total=0;
// Xav Modif
$idContract = GETPOST('id','int');
$lastId;
$itIsAContract = strpos($_SERVER['PHP_SELF'], 'contrat');
// fin Xav Modif

foreach($linkedObjectBlock as $object)
{
	$var=!$var;
	$lastId = $object->id;
        $socId = $object->socid;
?>
<tr <?php echo $GLOBALS['bc'][$var]; ?> ><td>
	<a href="<?php echo DOL_URL_ROOT.'/compta/facture.php?facid='.$object->id ?>"><?php echo img_object($langs->trans("ShowBill"),"bill").' '.$object->ref; ?></a></td>
	<td align="center"><?php echo dol_print_date($object->date,'day'); ?></td>
	<td align="right"><?php
		if ($user->rights->facture->lire) {
			$total = $total + $object->total_ht;
			echo price($object->total_ht);
		} ?></td>
	<td align="right"><?php echo $object->getLibStatut(3); ?></td>
</tr>
<?php
}
?>
<?php if ($itIsAContract !== false) { ?>
<tr >
    <td colspan="4" align="left">
        <a href="<?php echo DOL_URL_ROOT.'/compta/facture.php?action=createLast&facid='.$lastId .'&socid='.$socId. '&origin=contrat&originid='.$idContract ?>"> --- Créer la facture suivante automatiquement ---</a></td>
</tr>    
<?php }?>
<tr class="liste_total">
	<td align="left" colspan="2"><?php echo $langs->trans("TotalHT"); ?></td>
	<td align="right"><?php
		if ($user->rights->facture->lire) {
			echo price($total);
		} ?></td>
	<td>&nbsp;</td>
</tr>
</table>

<!-- END PHP TEMPLATE -->
