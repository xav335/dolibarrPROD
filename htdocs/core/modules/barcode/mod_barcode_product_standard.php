<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 * or see http://www.gnu.org/
 */

/**
 *       \file       htdocs/core/modules/barcode/mod_barcode_product_standard.php
 *       \ingroup    barcode
 *       \brief      File of class to manage barcode numbering with standard rule
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';


/**
 *	Class to manage barcode with standard rule
 */
class mod_barcode_product_standard extends ModeleNumRefBarCode
{
	var $nom='Standard';				// Model Name
	var $code_modifiable;				// Editable code
	var $code_modifiable_invalide;		// Modified code if it is invalid
	var $code_modifiable_null;			// Modified code if it is null
	var $code_null;						// Optional code
	var $version='dolibarr';    		// 'development', 'experimental', 'dolibarr'
	var $code_auto;                     // Automatic Numbering

	var $searchcode; // Search string
	var $numbitcounter; // Number of digits the counter
	var $prefixIsRequired; // The prefix field of third party must be filled when using {pre}


	/**
	 *	Constructor
	 */
	function __construct()
	{
		$this->code_null = 0;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**		Return description of module
	 *
	 * 		@param	Translate 		$langs		Object langs
	 * 		@return string      			Description of module
	 */
	function info($langs)
	{
		global $conf, $mc;
		global $form;

		$langs->load("products");

		$disabled = ((! empty($mc->sharings['referent']) && $mc->sharings['referent'] != $conf->entity) ? ' disabled="disabled"' : '');

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="BARCODE_STANDARD_PRODUCT_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("BarCode"),$langs->transnoentities("BarCode"));
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4c");
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Mask parameter
		//$texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("BarCodeModel").'):</td>';
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="value1" value="'.(! empty($conf->global->BARCODE_STANDARD_PRODUCT_MASK)?$conf->global->BARCODE_STANDARD_PRODUCT_MASK:'').'"'.$disabled.'>',$tooltip,1,1).'</td>';
		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"'.$disabled.'></td>';
		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Translate	$langs			Object langs
	 * @param	Product		$objproduct		Object product
	 * @return	string						Return string example
	 */
	function getExample($langs,$objproduct=0)
	{
		$examplebarcode = $this->getNextValue($objproduct,0);
		if (! $examplebarcode)
		{
			$examplebarcode = $langs->trans('NotConfigured');
		}
		if($examplebarcode=="ErrorBadMask")
		{
			$langs->load("errors");
			$examplebarcode=$langs->trans($examplebarcode);
		}

		return $examplebarcode;
	}

	/**
	 * Return next value
	 *
	 * @param	Product		$objproduct     Object product
	 * @param	string		$type       	type of barcode (EAN, ISBN, ...)
	 * @return 	string      				Value if OK, '' if module not configured, <0 if KO
	 */
	function getNextValue($objproduct,$type='')
	{
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		// TODO

		// Get Mask value
		$mask = '';
		if (! empty($conf->global->BARCODE_STANDARD_PRODUCT_MASK)) $mask = $conf->global->BARCODE_STANDARD_PRODUCT_MASK;

		if (empty($mask))
		{
			$this->error='NotConfigured';
			return '';
		}

		$field='barcode';$where='';

		$now=dol_now();

		$numFinal=get_next_value($db,$mask,'product',$field,$where,'',$now);

		return  $numFinal;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db					Database handler
	 *	@param	string		$code				Code to check/correct
	 *	@param	Product		$product			Object product
	 *  @param  int		  	$thirdparty_type   	0 = customer/prospect , 1 = supplier
	 *  @param	string		$type       	    type of barcode (EAN, ISBN, ...)
	 *  @return int								0 if OK
	 * 											-1 ErrorBadCustomerCodeSyntax
	 * 											-2 ErrorCustomerCodeRequired
	 * 											-3 ErrorCustomerCodeAlreadyUsed
	 * 											-4 ErrorPrefixRequired
	 */
	function verif($db, &$code, $product, $thirdparty_type, $type)
	{
		global $conf;

		//var_dump($code.' '.$product->ref.' '.$thirdparty_type);exit;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		$result=0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && empty($conf->global->BARCODE_STANDARD_PRODUCT_MASK))
		{
			$result=0;
		}
		else if (empty($code) && (! $this->code_null || ! empty($conf->global->BARCODE_STANDARD_PRODUCT_MASK)) )
		{
			$result=-2;
		}
		else
		{
			if ($this->verif_syntax($code, $type) >= 0)
			{
				$is_dispo = $this->verif_dispo($db, $code, $product);
				if ($is_dispo <> 0)
				{
					$result=-3;
				}
				else
				{
					$result=0;
				}
			}
			else
			{
				if (dol_strlen($code) == 0)
				{
					$result=-2;
				}
				else
				{
					$result=-1;
				}
			}
		}

		dol_syslog(get_class($this)."::verif type=".$thirdparty_type." result=".$result);
		return $result;
	}


	/**
	 *		Return if a code is used (by other element)
	 *
	 *		@param	DoliDB		$db			Handler acces base
	 *		@param	string		$code		Code a verifier
	 *		@param	Product		$product	Objet product
	 *		@return	int						0 if available, <0 if KO
	 */
	function verif_dispo($db, $code, $product)
	{
		$sql = "SELECT barcode FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE barcode = '".$code."'";
		if ($product->id > 0) $sql.= " AND rowid <> ".$product->id;

		$resql=$db->query($sql);
		if ($resql)
		{
			if ($db->num_rows($resql) == 0)
			{
				return 0;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			return -2;
		}

	}

	/**
	 *	Return if a barcode value match syntax
	 *
	 *	@param	string	$codefortest	Code to check syntax
     *  @param	string	$typefortest	Type of barcode (ISBN, EAN, ...)
	 *	@return	int						0 if OK, <0 if KO
	 */
	function verif_syntax($codefortest, $typefortest)
	{
		global $conf;

		$res = 0;

		// Get Mask value
		$mask = empty($conf->global->BARCODE_STANDARD_PRODUCT_MASK)?'':$conf->global->BARCODE_STANDARD_PRODUCT_MASK;
		if (! $mask)
		{
			$this->error='NotConfigured';
			return '';
		}

		$newcodefortest=$codefortest;
		if (in_array($typefortest,array('EAN13','ISBN')))	// We remove the CRC char not included into mask
		{
			$newcodefortest=substr($newcodefortest,0,12);
		}

		$result=check_value($mask,$newcodefortest);

		return $result;
	}

}

