<?
#
# Purpose: Ministry of Finance
# Created by: T.Bat-Erdene, 2011/04/06. 
# Update by: T.Bat-Erdene, 2017/02/23. 
# 2019.05.01	Ө.Түмэнжаргал	#connectiongbsb  болгов.
	include("../../config.inc.php");
	$PERMISSIONS = array("ETENDER_SEND","ETENDER_AMEND","ETENDER_CANCEL");
	include("../checkpermission.inc.php");
	include("../writelog.inc.php");
	
	#$connectiongb 
	#$connectiongb 
	#$connectiongbsb
	
	
	global $guaranteeIdForCancel;
	$gUrl="https://218.100.84.180:8080/BankGuaranteeWebService/BankGuaranteeWebService?wsdl";
	$gUrl="https://tender.gov.mn:8080/BankGuaranteeWebService/BankGuaranteeWebService?wsdl";

	$strSQL="select b.brchname, b.headname, b.addr,e.emplnameabr||'.'||e.empfname empname,b.email,nvl(b.phone,e.officephone) phone,b.fax
			from allweb_users u
				 left join hr.hr_employee@link2hr e on e.empid = u.empid
				 left join gb.pabrch b on b.brchno = u.branch
			where u.username='".@$_SESSION['ADMINUSER']."'";
	$result=@ociparse($connectiongbsb,$strSQL);
	@ociexecute($result,OCI_DEFAULT);
	@ocifetchinto($result,$gBrchInfo,OCI_ASSOC);

	$gDic=array("supplierTypeCode"=>array(1=>"INDIVIDUAL",3=>"ORGANIZATION"), //SupplierTypeCode
				"guaranteeTypeCode"=>array("TENDER_GUARANTEE"=>"Тендерийн баталгаа","PERFORMANCE_GUARANTEE"=>"Гүйцэтгэлийн баталгаа"/*,"02"=>"Цуцлах баталгаа"*/), //guaranteeTypeCode
				"guaranteeStatus"=>array("Илгээгдсэн","Цуцлагдсан"),
				"responseResultType"=>array("SUCCESS"=>"Амжилттай","FAILURE"=>"Амжилтгүй")
				);
	$gDicGa=array("supplierTypeCode"=>array(1=>"INDIVIDUAL",3=>"ORGANIZATION"), //SupplierTypeCode
			"guaranteeTypeCode"=>array("FOREIGN_TENDER_GUARANTEE"=>"Гадаад тендерийн баталгаа","FOREIGN_PERFORMANCE_GUARANTEE"=>"Гадаад гүйцэтгэлийн баталгаа" ,"FOREIGN_ADVANCE_FEE_GUARANTEE" => "Гадаад урьдчилгаа төлбөрийн баталгаа"/*,"02"=>"Цуцлах баталгаа"*/), //guaranteeTypeCode
			"guaranteeStatus"=>array("Илгээгдсэн","Цуцлагдсан"),
			"responseResultType"=>array("SUCCESS"=>"Амжилттай","FAILURE"=>"Амжилтгүй"),
			"BankCode"=>array("40000"=> "Худалдаа хөгжлийн банк", "10045"=> "NORDEA BANK AB", "10065"=> "BANK OF COMMUNICATIONS", "10001"=> "СБЕРБАНК", "HKBCCATT" => "HSBC BANK CANADA", "ВТБ" => "ВТБ 24", "PAO BANK" => "ПАО БАНК САНКТ-ПЕТЕРБУРК", "10003" => "Альфа-банк", "AO AB CIIADELE BANKA" => "AO AB CIIADELE BANKA",
							"CMB" => "China Merchants Bank", "СОБ"=>"Совкомбанк",  "ACBOC"=> "Agricultural Bank of China", "10007"=>"Дальневосточный банк", "10010"=>"China Minsheng Bank",
							"JPMorgan Chase Bank" => "JPMorgan Chase Bank", "ПАО БУБ"=>"ПАО БАНК УРАЛСИБ", "СБК"=> "Сбербанк Казахстан", "201117"=>"Транскапитал банк", "BOCH" => "Bank Of China",
							"10008"=>"China Everbright Bank", "10009"=>"Woori Bank", "CDB"=>"Хятад улсын бүтээн байгуулалтын банк", "Rabobank"=>"Рабобанк", "10005"=>"MUFG Bank", "BNK"=>"DANSKE bank",
							"20200228" => "Новикомбанк", "10004" => "Уралсиббанк", "8017"=>"Инг банк", "IB"=>"ING Bank", "RB"=>"Rabobank", "KT"=>"Kuveyt Turk", "PFB"=>"Прайм Финанс Банк", "CZB"=>"China Zheshang Bank",
							"CIBC"=>"Canadian imperial bank of commerce", "100210" => "Kookmin Bank", "ANZ"=>"ANZ bank", "EUB"=>"Eurasian Bank", "10006"=>"Банк оф Чайна", 
							"407028104"=>"ПАО Банка ФК Открытие", "CCB"=>"China Construction Bank", "100051"=>"Zamanbank", "absolute"=>"Абсолют банк", "BRCB"=>"Beijing Rural Commercial Bank",
							"RRB"=>"Русьрегион банк", "10011"=>"Komercni bank", "BNPP"=>"BNP Paribas", "ICBCL"=>"Industrial and Commercial Bank of China Limited", "BOIM"=>"Bank Of Inner Mongolia",
							"MFKB"=>"МФК банк", "PSBOC"=>"Postal savings bank of China", "IPB"=>"Интерпрогресс банк", "testbank" => "test bank")
			);
	$gRspCode=array("SUCCESS"=>"Амжилттай",
					"FAILURE"=>"Амжилтгүй",
					"ERR-001"=>"Сервир дээр алдаа гарлаа",
					"DATA-ERR-001"=>"XML өгөгдөл хоосон байна",
					"DATA-ERR-002"=>"XML өгөгдөл буруу байна",
					"USER-ERR-001"=>"Хэрэглэгчийн нэр хоосон байна",
					"USER-ERR-002"=>"Нууц үг хоосон байна",
					"USER-ERR-003"=>"Хэрэглэгчийн нэр эсвэл нууц үг буруу байна",
					"TENDER-ERR-001"=>"Тендерийн урилгын мэдээлэл олдсонгүй",
					"TENDER-ERR-002"=>"Тендерийн баримт бичгийн мэдээлэл олдсонгүй",
					"TENDER-ERR-003"=>"Тендерийн мэдээлэл олдсонгүй",
					"TENDER-ERR-004"=>"Урилгын дугаар хоосон байна",
					"TENDER-ERR-005"=>"Тендерийн код хоосон байна",
					"TENDER-ERR-006"=>"....... ийм кодтой тендер олдсонгүй",
					"TENDER-ERR-007"=>"Тендер ID хоосон байна",
					"GUARANTEE-ERR-001"=>"Урилгын дугаар хоосон байна",
					"GUARANTEE-ERR-002"=>"Урилгын мэдээлэл олдсонгүй",
					"GUARANTEE-ERR-003"=>"Захиалагчийн регистрийн дугаар хоосон байна",
					"GUARANTEE-ERR-004"=>"Захиалагчийн мэдээлэл олдсонгүй",
					"GUARANTEE-ERR-005"=>"Тендерт оролцогчийн регистрийн дугаар хоосон байна",
					"GUARANTEE-ERR-006"=>"Тендерт оролцогчийн мэдээлэл олдсонгүй",
					"GUARANTEE-ERR-007"=>"Баталгаа гаргагч банкны регистрийн дугаар хоосон байна",
					"GUARANTEE-ERR-008"=>"Баталгаа гаргагч банкны мэдээлэл олдсонгүй",
					"GUARANTEE-ERR-009"=>"Баталгааны төрлийн код хоосон байна",
					"GUARANTEE-ERR-010"=>"Баталгааны төрөл олдсонгүй",
					"GUARANTEE-ERR-011"=>"Валютын төрлийн код хоосон байна",
					"GUARANTEE-ERR-012"=>"Валютын төрөл олдсонгүй",
					"GUARANTEE-ERR-013"=>"Баталгааны эхлэх огноо хоосон байна",
					"GUARANTEE-ERR-014"=>"Баталгааны дуусах огноо хоосон байна",
					"GUARANTEE-ERR-015"=>"Баталгаа гаргасан мөнгөн дүн хоосон байна",
					"GUARANTEE-ERR-016"=>"Баталгаа гаргасан банкны удирдлагын нэр хоосон байна",
					"GUARANTEE-ERR-017"=>"Баталгаа гаргасан банкны ажилтаны нэр хоосон байна",
					"GUARANTEE-ERR-018"=>"Баталгаа гаргасан банкны ажилтаны утасны дугаар хоосон байна",
					"GUARANTEE-ERR-019"=>"Баталгаа гаргасан банкны ажилтаны имэйл хоосон байна",
					"GUARANTEE-ERR-020"=>"Нийлүүлэгчийн төрөл хоосон байна",
					"GUARANTEE-ERR-021"=>"Нийлүүлэгчийн төрөл олдсонгүй",
					"GUARANTEE-ERR-022"=>"Баталгаа гаргагч банкны код хоосон байна",
					"GUARANTEE-ERR-023"=>"Баталгааны мэдээлэл олдсонгүй",
					"GUARANTEE-ERR-024"=>"Баталгааны мэдээлэл аль хэдийн бүртгэгдсэн байна",
					"GUARANTEE-ERR-025"=>"Оролцож буй тендер байхгүй байна",
					"GUARANTEE-ERR-024"=>"Баталгааны мэдээлэл аль хэдийн бүртгэгдсэн байна",
					"GUARANTEE-ERR-024"=>"Баталгааны мэдээлэл аль хэдийн бүртгэгдсэн байна",
					"GUARANTEE-ERR-025"=>"Оролцож буй тендер байхгүй байна",
					"GUARANTEE-ERR-026"=>"Баталгааны ID хоосон байна",
					"GUARANTEE-ERR-027"=>"Баталгааны мэдээлэл олдсонгүй",
					"GUARANTEE-ERR-028"=>"Баталгааны мэдээллийг өөрчлөх боломжгүй байна",
					"GUARANTEE-ERR-029"=>"Баталгаа гаргах боломжгүй байна",
					"GUARANTEE-ERR-030"=>"Баталгаа гаргах тендерийн мэдээлэл хоосон байна");
	$rspData=array("bidderName"=>"Тендерт оролцогчийн нэр",
				   "bidderRegisterNumber"=>"Тендерт оролцогчийн регистрийн дугаар",
				   "budget"=>"Тендерийн төсөвт өртөг",
				   "clientName"=>"Захилагчийн нэр",
				   "clientRegisterNumber"=>"Захиалагчийн регистрийн дугаар",
				   "guaranteeAmount"=>"Тендерийн баталгаа",
				   "tenderCode"=>"Тендерийн код",
				   "tenderId"=>"Тендерийн урилгын дугаар",
				   "tenderName"=>"Тендерийн нэр",
				   "guaranteeEndDate"=>"Баталгааны дуусах огноо",
				   "guaranteeStartDate"=>"Баталгааны эхлэх огноо",
				   "guaranteeStatus"=>"Баталгааны төлөв",
				   "guaranteeId"=>"Баталгааны ID",
				   "currencyCode"=>"Валют код",
				   "currency"=>"Валют",
				   "bankCode"=>"Банкны код",
				   "bankName"=>"Банкны нэр",
				   "GuaranteeNumber"=>"Банкны баталгааны дугаар");

	#Document Type
	$vDocID=@$_REQUEST['xml']!=""?@$_REQUEST['xml']:2;
	$gDoc=array(0=>array("guaranteeBank", "Баталгаа олгоно"),
			 	1=>array("getTenders", "Нийлүүлэгчийн оролцож буй тендерийн мэдээллийг буцаана"),
				2=>array("getTenderList", "Тендерийн урилгын мэдээллийн хайлт", "Тендерийн мэдээллийг буцаана"),
				3=>array("getTenders", "Нийлүүлэгчийн оролцож буй тендерийн мэдээллийг буцаанаs"),
				4=>array("getGuaranteeInfo","Тендерийн баталгааны мэдээллийг буцаана"),
				5=>array("getCurrency","Системд бүртгэгдсэн валютын жагсаалтыг буцаана"),
				6=>array("getBankList","Системд бүртгэгдсэн банкны жагсаалтыг буцаана"),
				7=>array("getBankBranches","Системд бүртгэгдсэн банкны салбаруудын жагсаалтыг буцаана"),
				8=>array("updateGuarantee","Баталгаа засах"),
				9=>array("guaranteeReturn","Баталгаа цуцлах"),
				10=>array("guaranteeBank", "Баталгаа олгоно"),
				11=>array("guaranteeReturn","Баталгаа цуцлах")
		  );

	#Header Define
	$SndTransmission_Number="T".date("Ymdhis")."0001".@$gDoc[$vDocID][2]; #TranID length=20 байх ёстойг анхаарах. Сүүлийн 4-н орон дээр дугаар нэмэх
	if($vDocID==0)
	{
		$strSQL="SELECT count(*)+1 cnt FROM etender t WHERE regdate>=trunc(sysdate)";
		$result=@ociparse($connectiongbsb,$strSQL);
		@ociexecute($result,OCI_DEFAULT);
		@ocifetch($result);
		
		#TranID length=20 байх ёстойг анхаарах. Сүүлийн 4-н орон дээр дугаар нэмэх
		$SndTransmission_Number="T".date("Ymdhis").str_pad(@ociresult($result,"CNT"),4,"0",STR_PAD_LEFT).@$gDoc[$vDocID][2]; 
	}

	$gHeader=array("username"=>"tdbmongolia",
				   "password"=>"123456",
				   "SenderName"=>"Худалдаа Хөгжлийн Банк",
				   "SenderID"=>"2635534",
				   "ReceiverName"=>"Сангийн Яам", //Худалдан авах ажиллагааны бодлогын газар
				   "ReceiverID"=>"9131787",
				   "DocumentName"=>@$gDoc[$vDocID][1],
				   "DocumentID"=>@$gDoc[$vDocID][0],
				   "TransmissionID"=>$SndTransmission_Number, 
				   "DateTime"=>date("Y-m-d h:i:s"));
	if($sendType == 2)
        $gHeader=array("username"=>"ubcbank",
            "password"=>"123456",
            "SenderName"=>"Худалдаа Хөгжлийн Банк",
            "SenderID"=>"2635534",
            "ReceiverName"=>"Сангийн Яам", //Худалдан авах ажиллагааны бодлогын газар
            "ReceiverID"=>"9131787",
            "DocumentName"=>@$gDoc[$vDocID][1],
            "DocumentID"=>@$gDoc[$vDocID][0],
            "TransmissionID"=>$SndTransmission_Number,
            "DateTime"=>date("Y-m-d h:i:s"));
	$gCommon=array("PostalRelationAddress"=>"Монгол улс, Улаанбаатар хот, Жуулчны гудамж 7, Бага тойруу 12",
				   "Phone"=>"3123638, 331133",
				   "Fax"=>"(976-11) 327028",
				   "eMail"=>"tdbank@tdbm.mn",
				   "PostCode"=>"210646",
				   "BankCode"=>"40000");

	if(@$_REQUEST['xml']==998)
	{
		$strSQL="select l.refno,
					   l.custno,
					   c.retailcustno,
					   to_char(l.issuedate,'YYYY.MM.DD') issuedate,
					   case when l.expiryplace is not null then 'Тендер хүчинтэй байх хугацаанаас хойш 28 хоногийн хугацаанд'
							else to_char(l.expirydate,'YYYY.MM.DD') 
					   end expirydate,
					   l.curcode,
					   l.amount,
					   nvl(o.custname,o.custname2) custname,
					   l.appname,
					   l.appid,
					   l.appaddr,
					   l.appbranch,
					   l.benname,
					   l.notes,
					   l.postuserno,
					   u.username empname,					   
					   l.projectname,l.projectno,
					   trim(o.id1) id1,
					   b.brchname, b.headname,
					   l.amendno
				from ft.lgregm l
					 INNER JOIN ft.custregm c ON c.refno=l.custno
					 INNER JOIN gb.cust o ON o.custno=c.retailcustno
					 left join gb.pabrch b on b.brchno = l.appbranch 
					 LEFT JOIN se.seuser u ON u.userno=l.postuserno
	 			where l.refno='".@$_REQUEST['id']."'
					AND l.amendno='".@$_REQUEST['amend']."'";

		$result=@ociparse($connectiongbsb,$strSQL);
		@ociexecute($result,OCI_DEFAULT);
		@ocifetchinto($result,$rec,OCI_BOTH);
		if(@count((array)$rec)==0)
		{
			?>
            <script>
				var buttons = $(".rspGraft .ui-dialog-buttonpane button");
				$(buttons[0]).attr("disabled", 'disabled');
				$(buttons[0]).addClass("ui-state-disabled");
			</script>
            <?
			echo "<strong style=\"color:red\">Баталгааны мэдээлэл Грэйп Банк системээс олдсонгүй. Та баталгааныхаа дугаарыг шалгаад дахин хайна уу!</strong>";
			exit;
		}
		?>
        <table style="width:100%;" id="tblGuar">
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын нэр:</td>
                <td><input name="TenderName" title="Тендерийн урилгын нэрийг оруулна уу!" style="width:100%" type="text" value="<?=$rec["PROJECTNAME"]?>" id="TenderName" maxlength="250" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын дугаар:</td>
                <td><input name="TenderID" title="Тендерийн урилгын дугаарыг оруулна уу!" type="text" id="TenderID" size="15" value="<?=$rec["PROJECTNO"]?>" maxlength="11" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Захиалагч байгууллагын нэр:</td>
                <td><input name="OrgName" type="text" title="Захиалагч байгууллагын нэрийг оруулна уу!" id="OrgName" style="width:100%" value="<?=$rec["BENNAME"]?>" maxlength="200" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Захиалагч байгууллагын регистрийн дугаар:</td>
                <td><input name="OrgID" type="text" id="OrgID" title="Захиалагч байгууллагын регистрийн дугаарыг оруулна уу!" value="" size="15" maxlength="12"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н харилцагчийн дугаар:</td>
                <td><input name="TenderRetailCustNo" type="text" id="TenderRetailCustNo" value="<?=$rec["RETAILCUSTNO"]?>" readonly size="12"> <input name="TenderCustNo" type="text" id="TenderCustNo" size="6" value="<?=$rec["CUSTNO"]?>" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н нэр:</td>
                <td><input name="OrgName1" type="text" title="ААН-н нэрийг оруулна уу!" id="OrgName1" value="<?=$rec["CUSTNAME"]?>" style="width:100%" maxlength="200" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н регистрийн дугаар:</td>
                <td><input name="OrgID1" type="text" class="graft" id="OrgID1" title="ААН-н регистрийн дугаарыг оруулна уу!" size="15" maxlength="12" value="<?=$rec["ID1"]?>"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан салбарын нэр:</td>
                <td><input name="Brch" type="text" title="Баталгаа гаргасан салбарын нэрийг оруулна уу!" id="Brch" value="<?=@$rec["BRCHNAME"]?>" style="width:100%" maxlength="250" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан салбарын захирлын нэр:</td>
                <td><input name="BrchDir" type="text" title="Баталгаа гаргасан салбарын захирлын нэрийг оруулна уу!" id="BrchDir" value="<?=@$rec["HEADNAME"]?>" style="width:100%" maxlength="250" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааг хариуцсан ажилтан:</td>
                <td><input name="Emp" type="text" title="Баталгааг хариуцсан ажилтныг оруулна уу!" id="Emp" value="<?=@$rec["EMPNAME"]?>" maxlength="50" class="graft"></td>
            </tr>
            <tr> 
                <td width="5%" valign="top" nowrap>Баталгааны агуулга:</td>
                <td><textarea name="Comment" cols="45" rows="5" style="width:100%" id="Comment" maxlength="2000"></textarea></td>
            </tr>
            <tr> 
              <td width="5%" nowrap>Баталгааны дүн/валют:</td>
                <td><input type="text" name="Amt" title="Баталгааны дүн/валютыг оруулна уу!" id="Amt" value="<?=$rec["AMOUNT"]?>" class="graft"> <select name="CurCode" class="graft" id="CurCode">
                <?
					$strSQL="SELECT curcode,curname FROM gb.pacur c ORDER BY listorder";
					$result=@ociparse($connectiongbsb,$strSQL);
					@ociexecute($result,OCI_DEFAULT);
                	while(ocifetchinto($result,$row,OCI_BOTH))
						echo "<option value=\"".$row["CURCODE"]."\" ".($row["CURCODE"]==$rec["CURCODE"]?"selected":"")." title=\"".$row["CURNAME"]."\">".$row["CURCODE"]."</option>";
				?></select>
                </td>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан огноо:</td>
                <td><input name="StartDate" type="text" title="Баталгаа гаргасан огноог оруулна уу!" id="StartDate" value="<?=$rec["ISSUEDATE"]?>" size="12" maxlength="10" class="graft"> yyyy.mm.dd</td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааны хүчинтэй хугацаа:</td>
                <td><input name="EndDate" type="text" title="Баталгааны хүчинтэй хугацааг оруулна уу!" id="EndDate" value="<?=$rec["EXPIRYDATE"]?>" size="100" maxlength="100" class="graft" readonly></td>
            </tr>
        </table>
<?		
		exit;
	}
	
	#Create Request XML
	require_once("classWebservice.php");
	$xmlDom = new DomDocument('1.0','UTF-8');
	$xmlDom->formatOutput = true;
	
	//Header Setting
	$xmlEnvelope = $xmlDom->appendChild($xmlDom->createElement('soap-env:Envelope')); 
	$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
	$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
	$xmlBody = $xmlEnvelope->appendChild($xmlDom->createElement('soap-env:Body')); 
	if((@$_REQUEST['xml']=="1" || @$_REQUEST['xml']=="3") && @$_REQUEST['selType']!="")
		$xmlAction = $xmlBody->appendChild($xmlDom->createElement("web:".@$gDoc[4][0])); 
	else 
		$xmlAction = $xmlBody->appendChild($xmlDom->createElement("web:".@$gDoc[$vDocID][0])); 

	$xmlItm = $xmlAction->appendChild($xmlDom->createElement('username')); 
	$xmlItm->appendChild($xmlDom->createTextNode($gHeader["username"])); 
	$xmlItm = $xmlAction->appendChild($xmlDom->createElement('password')); 
	$xmlItm->appendChild($xmlDom->createTextNode($gHeader["password"])); 

	switch(@$_REQUEST['xml'])
	{
		case "5": #Валютын мэдээлэл
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: getCurrency", $Soap);
			
			$Service = new Webservice($gUrl, "SOAP", "utf-8");	
			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
			fnWrite2Log("ETENDER", "Response: getCurrency", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
				print_r($Response);
				echo "</p>";
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->getCurrencyResponse->return->responseResultType;

			//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getCurrencyResponse->return->failureMessages->message->failureCode!="") 
			{
				echo "<p style=\"color:red\"><br>Fail !!!";
				$Result_Code = $xml->Body->getCurrencyResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->getCurrencyResponse->return->description."<br>".$xml->Body->getCurrencyResponse->return->failureMessages->message->failureMessage;
				echo "<br>Алдааны код: ".$Result_Code;
				echo "<br>Алдааны мэдээлэл: ".$Result_Message."</p>";
				break;
			}
			$iRow=0;
			foreach($xml->Body->getCurrencyResponse->return->currency as $key=>$val)
			{
				if($iRow==0)
				{
					echo "<table><tr><td>№</td>";
					foreach($val as $k=>$v)
					{
						echo "<td nowrap><strong>".@$rspData[$k]."</strong></td>";
					}
					echo "</tr>";
				}
				echo "<tr><td>".(++$iRow).".</td>";
				foreach($val as $k=>$v)
					echo "<td nowrap>".$v."</td>";
				echo "</tr>";
			}
			break;
		case "6": #Банкны мэдээлэл
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: getBankList", $Soap);
			
			$Service = new Webservice($gUrl, "SOAP", "utf-8");

			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
			fnWrite2Log("ETENDER", "Response: getBankList", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
				print_r($Response);
				echo "</p>";
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->getBankListResponse->return->responseResultType;

			//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getBankListResponse->return->failureMessages->message->failureCode!="") 
			{
				echo "<p style=\"color:red\"><br>Fail !!!";
				$Result_Code = $xml->Body->getBankListResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->getBankListResponse->return->description."<br>".$xml->Body->getBankListResponse->return->failureMessages->message->failureMessage;
				echo "<br>Алдааны код: ".$Result_Code;
				echo "<br>Алдааны мэдээлэл: ".$Result_Message."</p>";
				break;
			}
			$iRow=0;
			foreach($xml->Body->getBankListResponse->return->bankList as $key=>$val)
			{
				if($iRow==0)
				{
					echo "<table><tr><td>№</td>";
					foreach($val as $k=>$v)
					{
						echo "<td nowrap><strong>".@$rspData[$k]."</strong></td>";
					}
					echo "</tr>";
				}
				echo "<tr><td>".(++$iRow).".</td>";
				foreach($val as $k=>$v)
					echo "<td nowrap>".$v."</td>";
				echo "</tr>";
			}
			break;
		case "7": #Салбарын мэдээлэл
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('bankCode')); 
			$xmlItm->appendChild($xmlDom->createTextNode($gCommon["BankCode"])); 
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: getBankBranches", $Soap);
			
			$Service = new Webservice($gUrl, "SOAP", "utf-8");
			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
			fnWrite2Log("ETENDER", "Response: getBankBranches", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
				print_r($Response);
				echo "</p>";
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->getBankBranchesResponse->return->responseResultType;

			//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getBankBranchesResponse->return->failureMessages->message->failureCode!="") 
			{
				echo "<p style=\"color:red\"><br>Fail !!!";
				$Result_Code = $xml->Body->getBankBranchesResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->getBankBranchesResponse->return->description."<br>".$xml->Body->getBankBranchesResponse->return->failureMessages->message->failureMessage;
				echo "<br>Алдааны код: ".$Result_Code;
				echo "<br>Алдааны мэдээлэл: ".$Result_Message."</p>";
				break;
			}
			$iRow=0;
			foreach($xml->Body->getBankBranchesResponse->return->bankList as $key=>$val)
			{
				if($iRow==0)
				{
					echo "<table><tr><td>№</td>";
					foreach($val as $k=>$v)
					{
						echo "<td nowrap><strong>".@$rspData[$k]."</strong></td>";
					}
					echo "</tr>";
				}
				echo "<tr><td>".(++$iRow).".</td>";
				foreach($val as $k=>$v)
					echo "<td nowrap>".$v."</td>";
				echo "</tr>";
			}
			break;
		case "9": #Тендерийн баталгаа цуцлах
			$strSQL="SELECT nvl(c.custname,c.custname2) custname,trim(c.id1) id1, trim(c.id2) id2,nvl(c.dirname,c.dirname2) dirname,
							c.handphone,c.fax,c.workphone,c.homephone,c.email,to_char(c.regdate,'YYYY/MM/DD') regdate,
							c.typecode,c.statuscode,d.flddesc
					FROM gb.cust c 
					LEFT JOIN dic_db d ON dbuser='gb' AND tblname='cust' AND fldname='statuscode' AND fldvalue=c.statuscode
					WHERE trim(id1)='".@$_REQUEST['txtID']."' ORDER BY decode(c.statuscode,4,1,2)";

			$result=ociparse($connectiongbsb,$strSQL);
			ociexecute($result,OCI_DEFAULT);
			$row=array();
			@ocifetchinto($result,$row,OCI_ASSOC);
			if($row["ID1"]=="")
			{
				echo "<b style=\"color:red\">ГБ системээс харилцагчийн мэдээлэл хайлтаар олдсонгүй!</b>";
				break;
			}

			if($row["STATUSCODE"]!="4")
			{
				echo "<b style=\"color:red\">ГБ систем дээр харилцагч идэвхигүй төлөвтэй байгаа тул тендерийн мэдээллийг лавлах боломжгүй байна!</b>";
				break;
			}
			
			$xmlDomForCancel = new DomDocument('1.0','UTF-8');
			$xmlDomForCancel->formatOutput = true;
			
			//Header Setting
			$xmlEnvelope = $xmlDomForCancel->appendChild($xmlDomForCancel->createElement('soap-env:Envelope')); 
			$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
			$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
			$xmlBodyForCancelation = $xmlEnvelope->appendChild($xmlDomForCancel->createElement('soap-env:Body')); 
			$xmlActionForCancel = $xmlBodyForCancelation->appendChild($xmlDomForCancel->createElement("web:".@$gDoc[9][0])); 
		
			$xmlItmForCancel = $xmlActionForCancel->appendChild($xmlDomForCancel->createElement('username')); 
			$xmlItmForCancel->appendChild($xmlDomForCancel->createTextNode($gHeader["username"])); 
			$xmlItmForCancel = $xmlActionForCancel->appendChild($xmlDomForCancel->createElement('password')); 
			$xmlItmForCancel->appendChild($xmlDomForCancel->createTextNode($gHeader["password"])); 
			

			$xmlItmForCancel = $xmlActionForCancel->appendChild($xmlDomForCancel->createElement('guaranteeId')); 
			$xmlItmForCancel->appendChild($xmlDomForCancel->createTextNode(@$_REQUEST['id']));
			
			
			$SoapForCancel = $xmlDomForCancel->saveXML();
			fnWrite2Log("ETENDER", "Request: guaranteeReturn", $SoapForCancel);

			$Service = new Webservice($gUrl, "SOAP", "utf-8");
			flush();
			$ResponseForCancel = $Service->SendRequest(str_replace("\'","'",$SoapForCancel), "guaranteeReturn");
			fnWrite2Log("ETENDER", "ResponseForCancel: guaranteeReturn", @$ResponseForCancel["Body"]);
			if($ResponseForCancel["Status"]!=200)
			{
				echo json_encode(array("error"=>"1","msg"=>"Баталгааг цуцалж чадсангүй холболтын алдаа гарлаа!\nТа дахин баталгааг цуцлана уу."));
				// break;
			}
			// echo json_encode(array("error"=>"400","msg"=>$ResponseForCancel["Body"]));
			fnWrite2Log("ETENDER", "guaranteeReturn: Empid", @$_SESSION['EMPID']);
			$strSQL="UPDATE etender SET typecode='02',receivetime1=sysdate,resultmessage1='Цуцлагдсан',cnclempid = ".@$_SESSION['EMPID'].", resultcode1='SUCCESS',transmissionid1='".$SndTransmission_Number."'
					WHERE tenderno='".@$_REQUEST['id']."' 
						AND status=1";
			$result=@ociparse($connectiongb,$strSQL);
			if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
			{
				$strMsg="Баталгааны бичилтийг хийж чадсангүй алдаа гарлаа\n";
				$strMsg.="Цуцалж байгаа тендерийн төлвийг шалгана уу!\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
				echo json_encode(array("error"=>"1","msg"=>$strMsg));
				break;
			}
			$strMsg="Баталгаа амжилттай цуцлагдлаа";
			echo json_encode(array("error"=>"0","msg"=>$strMsg));
			break;	
		case "2": #Тендерийн мэдээлэл
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('invitationNumber')); 
			$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtNumberID']))); 
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: getTenderList", $Soap);
			
			$Service = new Webservice($gUrl, "SOAP", "utf-8");
			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), @$Action);

			if($_SESSION["EMPID"]==20178 || $_SESSION["EMPID"] == 48806)
			{
				print_r($Soap);
				print_r($Response);
			}
			
			fnWrite2Log("ETENDER", "Response: getTenderList", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
				print_r($Response);
				echo "</p>";
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);

			if($_SESSION["EMPID"]==20178 || $_SESSION["EMPID"] == 48806)
			{
				print_r($strData);
			}

			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->getTenderListResponse->return->responseResultType;

			//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getTenderListResponse->return->failureMessages->message->failureCode!="") 
			{
				echo "<p style=\"color:red\"><br>Fail !!!";
				$Result_Code = $xml->Body->getTenderListResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->getTenderListResponse->return->description."<br>";
				foreach($xml->Body->getTenderListResponse->return->failureMessages->message as $k=>$v)
					$Result_Message .= $v->failureCode.". ".$v->failureMessage."<br>";
				echo "<br>Алдааны код: ".$Result_Code;
				echo "<br>Алдааны мэдээлэл: ".$Result_Message;
				echo "<br><br>Тендерийн мэдээллийг Төрийн худалдан авах ажиллагааны цахим системээс лавлаж, цахим тендер эсэх, хүчинтэй байгаа эсэх, материал хүлээн авах хугацаа дуусаагүй байгаа эсэх зэрэг мэдээллийг шалгана уу!</p>";
				break;
			}
			?>
			<table>
				<caption>Тендерийн мэдээлэл</caption>
				<?
				foreach($xml->Body->getTenderListResponse->return->tenderInfo->children() as $key=>$val)
				{
					if($key=="tenderPackageList") continue;
					?>
				  <tr>
					<td width="25%"><strong><?=@$rspData[$key]!=""?@$rspData[$key]:$key?></strong>:</td>
					<td><?=in_array($key,array("budget","guaranteeAmount"))?number_format(strval($val),3):$val?></td>
				  </tr>
					<?
				}

				#getTenderList
				$tenders=$xml->xpath("/Envelope/Body/getTenderListResponse/return/tenderInfo/tenderPackageList/tenderName");
				if(count((array)$tenders)>0)
				{
				?>
				<tr><td colspan="2">
				  <div id="divTenderLst" style="width:100%">
					<ul>
					<?
					foreach($tenders as $k=>$v)
					{
						?>
						<li><a href="#divTenderLst<?=$k?>"><?=$v?></a></li>
						<?
					}
				?>
					</ul>
				<?
					$tenders=$xml->xpath("/Envelope/Body/getTenderListResponse/return/tenderInfo/tenderPackageList");
					foreach($tenders as $k=>$v)
					{
				?>
					<div id="divTenderLst<?=$k?>" style="width:100%; padding:0; margin:0;">
				<?
						foreach($v->children() as $kk=>$vv)
						{
				?>
						<div style="width:25%; white-space:nowrap; float:left"><strong><?=@$rspData[$kk]!=""?@$rspData[$kk]:$kk?></strong>:</div>
						<div style="width:75%; white-space:nowrap; float:left"><?=in_array($kk,array("budget","guaranteeAmount"))?number_format(strval($vv),3):$vv?></div>
				<?
						}
				?>
					</div>
				<?
					}
				?>
				  </div>
				</td></tr>
				<script>$( "#divTenderLst" ).tabs();</script>
                <? }?>
			</table>
            <?
			break;
		case "1": #Харилцагчийн мэдээлэл
			$strSQL="SELECT nvl(c.custname,c.custname2) custname,trim(c.id1) id1, trim(c.id2) id2,nvl(c.dirname,c.dirname2) dirname,
                            c.handphone,c.fax,c.workphone,c.homephone,c.email,to_char(c.regdate,'YYYY/MM/DD') regdate,
                            c.typecode,c.statuscode,d.flddesc
					   FROM gb.cust c 
					   LEFT JOIN dic_db d ON dbuser='gb' AND tblname='cust' AND fldname='statuscode' AND fldvalue=c.statuscode
					  WHERE trim(id1)='".@$_REQUEST['txtID']."' ORDER BY decode(c.statuscode,4,1,2)";
		
			$result=ociparse($connectiongbsb,$strSQL);
			ociexecute($result,OCI_DEFAULT);
			$row=array();
			@ocifetchinto($result,$row,OCI_ASSOC);
			if($row["ID1"]=="")
			{
				echo "<b style=\"color:red\">ГБ системээс харилцагчийн мэдээлэл хайлтаар олдсонгүй!</b>";
				break;
			}
			?>
			<table border="0" cellspacing="0" cellpadding="2" style="margin-top:16px;" width="100%">
			  <tr>
				<td colspan="2" nowrap style="background-color:green; color:<?=$row["STATUSCODE"]=="4"?"white":"red"?>;"><strong>I. Грэйп банк дахь харилцагчийн мэдээлэл</strong></td>
              </tr>
			  <tr>
				<td width="25%"><strong>Харилцагчийн нэр</strong>:</td>
				<td><?=$row["CUSTNAME"]?></td>
              </tr>
              <tr>
				<td><strong>Регистрийн дугаар</strong>:</td>
				<td><?=$row["ID1"]?> <?=$row["ID2"]?></td>
              </tr>
              <tr>
				<td><strong>Захирлын нэр</strong>:</td>
				<td><?=$row["DIRNAME"]?></td>
              </tr>
              <tr>
				<td><strong>Утасны дугаар</strong>:</td>
				<td><?=$row["HANDPHONE"]." ".$row["WORKPHONE"]." ".$row["HOMEPHONE"]?></td>
              </tr>
              <tr>
				<td><strong>Факс</strong>:</td>
				<td><?=$row["FAX"]?></td>
              </tr>
              <tr>
				<td><strong>и-Мэйл</strong>:</td>
				<td><?=$row["EMAIL"]?></td>
              </tr>
              <tr>
				<td><strong>Бүртгүүлсэн огноо</strong>:</td>
				<td><?=$row["REGDATE"]?></td>
              </tr>
              <tr>
				<td><strong>Төлөв</strong>:</td>
				<td><?=$row["FLDDESC"]?></td>
			  </tr>
			</table>
			<?	
			if($row["STATUSCODE"]!="4")
			{
				echo "<b style=\"color:red\">ГБ систем дээр харилцагч идэвхигүй төлөвтэй байгаа тул тендерийн мэдээллийг лавлах боломжгүй байна!</b>";
				break;
			}
			
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('invitationNumber')); 
			$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtNumberID']))); 
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('bidderRegisterNumber')); 
			$xmlItm->appendChild($xmlDom->createTextNode(@$row['ID1'])); 
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('supplierTypeCode')); 
			$xmlItm->appendChild($xmlDom->createTextNode(@$gDic["supplierTypeCode"][@$row['TYPECODE']]));
			
			#Amend Section
			$strGuaranteeInfo="";
			$gGuaranteeID="";

			if($_REQUEST["selType"]!="")
			{
				$strSQL="SELECT t.tenderno
						   FROM etender t
						  WHERE orgid1='".@$_REQUEST['txtID']."'
							AND invitationnumber='".trim(@$_REQUEST['txtNumberID'])."' 
							AND status=1";
				$result=@ociparse($connectiongbsb,$strSQL);		
				@ociexecute($result,OCI_DEFAULT);
				$recTender=array();
				ocifetchinto($result,$recTender,OCI_ASSOC);				
				
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeTypeCode')); 
				$xmlItm->appendChild($xmlDom->createTextNode(@$_REQUEST["selType"]));
				$Soap = $xmlDom->saveXML();
				fnWrite2Log("ETENDER", "Request: soap", $Soap);
				
				$Service = new Webservice($gUrl, "SOAP", "utf-8");

				flush();
				$Response = $Service->SendRequest(str_replace("\'","'",$Soap), @$Action);
				fnWrite2Log("ETENDER", "Response: response body", @$Response["Body"]);
				$guaranteeIdForCancel = @$Response["Body"];
				if($Response["Status"]!=200)
				{
					echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
					print_r($Response);
					echo "</p>";
					if($_SESSION['EMPID'] == 27350){
						echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
						print_r($Soap);
						echo "</p>";
					}
					break;
				}
				$strData=str_replace("S:","",$Response["Body"]);
				$strData=str_replace("ns2:","",$strData);
				try {
					$xml = @new SimpleXMLElement($strData);
				}
				catch(Exception $e){
					print_r($e);
					break;
				}
				//Common Header Part
				$Receive_Time = date("Y/m/d h:i:s");
				$Result_Code = $xml->Body->getGuaranteeInfoResponse->return->responseResultType;

				//Success
				if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getGuaranteeInfoResponse->return->failureMessages->message->failureCode!="")
				{
					echo "<p style=\"color:red\"><br>Fail !!!";
					$Result_Code = $xml->Body->getGuaranteeInfoResponse->return->failureMessages->message->failureCode;
					$Result_Message = $xml->Body->getGuaranteeInfoResponse->return->description."<br>";
					foreach($xml->Body->getGuaranteeInfoResponse->return->failureMessages->message as $k=>$v)
						$Result_Message .= $v->failureCode.". ".$v->failureMessage."<br>";
					
					echo "<br>Алдааны код: ".$Result_Code;
					echo "<br>Алдааны мэдээлэл: ".$Result_Message,"</p>";
					break;
				}
				#Begin Банкнаас илгээсэн харилцагчийн баталгаанууд
				$gGuaranteeID=base64_encode(gzcompress($strData,9));
				ob_start();
				?>
                  <tr>
                    <td colspan="2" style="background-color:green; color:white" title="Банкнаас Цахим тендерийн системд илгээсэн байгаа баталгаанууд"><strong>II. Тендерийн бүртгэлийн мэдээлэл (Банкнаас Цахим тендерийн системд илгээсэн байгаа баталгаанууд)</strong></td>
                  </tr>
                  <tr>
                    <td colspan="2">
                    <table title="Банкнаас Цахим тендерийн системд илгээсэн байгаа баталгаанууд">
                    	<tr>
							<?
                            foreach($xml->Body->getGuaranteeInfoResponse->return->guaranteeInfo as $key=>$val)
                            {
                                foreach($val as $kk=>$vv)
                                {
                                    ?>
                                    <td style="background-color:e78f08; color:white"><strong><?=@$rspData[$kk]!=""?@$rspData[$kk]:$kk?></strong></td>
                                    <?
                                }
								
								?>
								<td style="background-color:e78f08; color:white"><strong>Үйлдэл</strong></td>
								<!-- Товч нэмээд тухайн товч дарах үед тендер цуцлах хүсэлт илгэхдээ guaranteeId авна. -->
								<?
							
								break;
                            }
                            ?>
                        </tr>
                    <?
					$amtTotal=0;
					foreach($xml->Body->getGuaranteeInfoResponse->return->guaranteeInfo as $key=>$val)
					{
						?>
                        <tr>
                        <?
						foreach($val as $kk=>$vv)
						{
							?>
							<td><?=in_array($kk,array("budget","guaranteeAmount"))?number_format(strval($vv),3):$vv?></td>
							<?
						}
						?>
						<?
							if($val->guaranteeStatus=="Цуцалсан")
								{?>
									<td colspan="2" height="20">
    									<button style="height:30px;" id="<?=@$val->guaranteeId?>" onClick="handleButtonClick(this.id)">
        								<img src="../images/cancel.png" align="absmiddle"> Цуцлах
    									</button>
									</td>
								<?	
								}
							?>
                        </tr>
                        <?
						$amtTotal+=floatval($val->guaranteeAmount);
					}
					?>
                    </table>
                    <p><strong>Илгээсэн баталгааны нийт дүн:</strong> <strong><?=number_format($amtTotal,3)?></strong></p>
                    </td>
                  </tr>
                <?
				#End Банкнаас илгээсэн харилцагчийн баталгаанууд
				?>
				  <tr>
					<td colspan="2" height="20"></td>
				  </tr>
				<?
				$strGuaranteeInfo=ob_get_contents();
				ob_end_clean();

				//Active tender
				if($xml->Body->getGuaranteeInfoResponse->return->guaranteeInfo->guaranteeStatus=="Цуцалсан")
				{
				  if(@$_REQUEST["cmd"]=="amend") 
				  {
					?>
					<table border="0" cellspacing="0" cellpadding="2" style="margin-top:16px;" width="100%">
					  <tr><td width="25%"></td><td></td></tr>
					  <tr>
						<td colspan="2" style="background-color:red; color:white"><strong>II. Тендерийн бүртгэлийн мэдээлэл</strong></td>
					  </tr>
                	<tr>
                    <td colspan="2" style="color:red;" height="40"><strong>Тендерийн баталгааг засах боломжгүй, уг баталгаа цуцлагдсан байна!!!</strong></td>
                  </tr>
                  <?
				  }
				  echo $strGuaranteeInfo;
				  ?>
                </table>
                    <?
					break;
				}
				
				$xmlDom = new DomDocument('1.0','UTF-8');
				$xmlDom->formatOutput = true;
				
				//Header Setting
				$xmlEnvelope = $xmlDom->appendChild($xmlDom->createElement('soap-env:Envelope')); 
				$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
				$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
				$xmlBody = $xmlEnvelope->appendChild($xmlDom->createElement('soap-env:Body')); 
				$xmlAction = $xmlBody->appendChild($xmlDom->createElement("web:".@$gDoc[$vDocID][0])); 
			
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('username')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["username"])); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('password')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["password"])); 
				
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('invitationNumber')); 
				$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtNumberID']))); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('bidderRegisterNumber')); 
				$xmlItm->appendChild($xmlDom->createTextNode(@$row['ID1'])); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('supplierTypeCode')); 
				$xmlItm->appendChild($xmlDom->createTextNode(@$gDic["supplierTypeCode"][@$row['TYPECODE']]));
			
			} #End Amend Section

			?>
			<table border="0" cellspacing="0" cellpadding="2" style="margin-top:16px;" width="100%">
              <tr><td width="25%"></td><td></td></tr>
			<?
			echo $strGuaranteeInfo;
			
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: getTenders", $Soap);
			
			$Service = new Webservice($gUrl, "SOAP", "utf-8");

			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), @$Action);
			fnWrite2Log("ETENDER", "Response: getTenders", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
				print_r($Response);
				echo "</p>";
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
            $strData=str_replace("''","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->getTendersResponse->return->responseResultType;

            //Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getTendersResponse->return->failureMessages->message->failureCode!="")
			{
				echo "<p style=\"color:red\"><br>Fail !!!";
				$Result_Code = $xml->Body->getTendersResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->getTendersResponse->return->description."<br>";
				foreach($xml->Body->getTendersResponse->return->failureMessages->message as $k=>$v)
					$Result_Message .= $v->failureCode.". ".$v->failureMessage."<br>";
				echo "<br>Алдааны код: ".$Result_Code;
				echo "<br>Алдааны мэдээлэл: ".$Result_Message,"</p>";
				break;
			}
			?>
			  <tr>
				<td colspan="2" nowrap style="background-color:green; color:white;"><strong>III. Харилцагчийн сонгосон тендер болон багцууд</strong></td>
              </tr>
			<?

			foreach($xml->Body->getTendersResponse->return->tenderInfo->children() as $key=>$val)
			{
				if($key=="tenderPackageList") continue;
				?>
              <tr>
				<td><strong><?=@$rspData[$key]!=""?@$rspData[$key]:$key?></strong>:</td>
				<td><?=in_array($key,array("budget","guaranteeAmount"))?number_format(strval($val),3):$val?></td>
              </tr>
                <?
			}

			$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList/tenderName");

			if(count((array)$tenders)>0)
			{
			?>
                <tr><td colspan="2">
                  <div id="divTenders" style="width:100%">
                    <ul>
                    <?
					foreach($tenders as $k=>$v)
					{
						?>
						<li><a href="#divTender<?=$k?>"><?=$v?></a></li>
                        <?
					}
				?>
                    </ul>
                <?
					$amtTotal=0;
					$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");

					foreach($tenders as $k=>$v)
					{
				?>
                    <div id="divTender<?=$k?>" style="width:100%; padding:0; margin:0;">
                <?
						foreach($v->children() as $kk=>$vv)
						{
				?>
                        <div style="width:25%; white-space:nowrap; float:left"><strong><?=@$rspData[$kk]!=""?@$rspData[$kk]:$kk?></strong>:</div>
                        <div style="width:75%; white-space:nowrap; float:left"><?=in_array($kk,array("budget","guaranteeAmount"))?number_format(strval($vv),3):$vv?></div>
                <?
						}
				?>
                    </div>
				<?
						$amtTotal+=floatval($v->guaranteeAmount);
					}
				?>
                  </div>
				</td></tr>
                <tr><td colspan="2" height="30"><strong>Сонгосон баталгааны нийт дүн:</strong> <strong><?=number_format($amtTotal,3)?></strong></td></tr>
              <? }?>
            </table>
            <script>
				<? if(count((array)$tenders)>0) {?>$( "#divTenders" ).tabs();<? }?>
				$('#txtTenderName').val("<?=trim(str_replace('"', "" , str_replace("'", "" ,str_replace('\\', '/', $xml->Body->getTendersResponse->return->tenderInfo->tenderName))))?>");
				<?
					$tenderID=@$xml->Body->getTendersResponse->return->tenderInfo->tenderPackageList->tenderId;
					if($tenderID=="") $tenderID=$xml->Body->getTendersResponse->return->tenderInfo->tenderId;
					
					$tenCode=@$xml->Body->getTendersResponse->return->tenderInfo->tenderPackageList->tenderCode;
					if($tenCode=="") $tenCode=$xml->Body->getTendersResponse->return->tenderInfo->tenderCode;
					
					$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");
					if(count((array)$tenders)>1) #1-с их багцийн тендертэй бол
					{
						$strTenders="";
						foreach($tenders as $kkk=>$vvv)
						{
							$strTenders.="<option value=\"".$vvv->tenderId.":".$vvv->tenderCode."\">".$vvv->tenderId." - ".$vvv->tenderCode.", ".$vvv->tenderName."</option>";
						}
						if(@$_REQUEST["cmd"]=="amend"/*|| !in_array($_SESSION["EMPID"],array(415,1045))*/)
						{
						?>
							$('#tdTenderID').html('<select name="txtTenderID" class="guar" id="txtTenderID" style="width: 100%" title="Тендерийн урилгын дугаарыг оруулна уу!"><option value=\"\"><?=@$_REQUEST["cmd"]=="amend"?"Засах":"Илгээх"?> багцаа сонгоно уу</option><?=$strTenders?></select>');
						<? 
						}
						else
						{
						?>
							$('#tdTenderID').html('<select name="txtTenderID[]" multiple="multiple" class="guar" id="selTenderID" style="width: 100%" title="Тендерийн урилгын дугаар буюу илгээх багцийг сонгоно уу!"><?=$strTenders?></select>');
							$("#selTenderID").multipleSelect({filter: true, placeholder: '<?=@$_REQUEST["cmd"]=="amend"?"Засах":"Илгээх"?> багцаа сонгоно уу',selectAllText: 'Бүх багцийг сонгох'});
						<? 
						}
					}
					else { #1 багцтай болон багцгүй тендер бол
					?>
					$('#tdTenderID').html('<input name="txtTenderID" type="text" class="guar" id="selTenderID" title="Тендерийн урилгын дугаарыг оруулна уу!" style="width: 100%" maxlength="40" readonly value="<?=$tenderID.":".$tenCode?>">');
				<?  }?>
				
				$('#txtOrgName1').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->bidderName?>');
				$('#txtOrgID1').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->bidderRegisterNumber?>');
				$('#txtOrgName').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->clientName?>');
				$('#txtOrgID').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->clientRegisterNumber?>');

				$('#divTenderData').html($('#divTenderData').html()+'<input type="hidden" name="tenInvitationNumber" id="tenInvitationNumber" value="<?=trim(@$_REQUEST['txtNumberID'])?>">');
				$('#divTenderData').html($('#divTenderData').html()+'<input type="hidden" name="tenSupplierTypeCode" id="tenSupplierTypeCode" value="<?=@$gDic["supplierTypeCode"][@$row['TYPECODE']]?>">');
				$('#divTenderData').html($('#divTenderData').html()+'<input type="hidden" name="tenOrgTenderData" id="tenOrgTenderData" value="<?=base64_encode(@gzcompress($strData,9))?>">');
				<? if(@$gGuaranteeID!="") {?>$('#divTenderData').html($('#divTenderData').html()+'<input type="hidden" name="tenGuaranteeID" id="tenGuaranteeID" value="<?=@$gGuaranteeID?>">');<? }?>

				
            </script>
			<?
			break;
		case "0": #Баталгаа гаргах
			$strSQL="SELECT count(*) cnt FROM gb.cust c WHERE trim(c.id1)='".@$_REQUEST['txtOrgID1']."' AND c.statuscode<>'0'";
			$result=@ociparse($connectiongbsb,$strSQL);
			@ociexecute($result,OCI_DEFAULT);
			@ocifetch($result);
			if(@ociresult( $result,"CNT")==0)
			{
				fnWrite2Log("ETENDER", "ERROR: Customer register", @$strSQL);
				$strMsg="Харилцагчийн регистрийн дугаар Цахим тендерийн систем дээрх бүртгэлээс\nзөрүүтэй байгаа тул баталгааг илгээж чадсангүй!\n";
				$strMsg.="Илгээж байгаа харилцагчийн бүртгэлийг шалгаад, дахин илгээнэ үү!\n";
				echo json_encode(array("error"=>"1","msg"=>$strMsg));
				break;
			}
			if(@$_REQUEST['tenGuaranteeID']!="") 
			{ #Begin Засвар орж буй хуучин баталгааг идэвхигүй болгох
				$strSQL="UPDATE etender SET status='0',
							receivetime1=sysdate,
							resultmessage1='Засагдсан',
							resultcode1='SUCCESS',
							transmissionid1='".$SndTransmission_Number."'
						WHERE custno='".@$_REQUEST['txtOrgRetailCustNo']."'
							AND invitationnumber='".@$_REQUEST['tenInvitationNumber']."' 
							AND tenderid='".@$_REQUEST['txtTenderID']."'
							AND status=1";
				fnWrite2Log("ETENDER", "UPDATE", @$strSQL);
				$result=@ociparse($connectiongb,$strSQL);
				if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
				{
					$strMsg="Өөрчлөлт хийх баталгааны мэдээлэл олдсонгүй\n";
					$strMsg.="Та тендерийн мэдээллийг шалгана уу!\n";
					$strMsg.="------------------------------------------------------\n";
					$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
					echo json_encode(array("error"=>"1","msg"=>$strMsg));
					break;
				}
			} #End Засвар орж буй хуучин баталгааг идэвхигүй болгох
			
			if(is_array($_REQUEST["txtTenderID"]))
			{ #Begin Нэг баталгаатай багц тендер
				$strData=gzuncompress(base64_decode(@$_REQUEST["tenOrgTenderData"]));
				$strData=str_replace("S:","",$strData);
				$strData=str_replace("ns2:","",$strData);
				$xml = @new SimpleXMLElement($strData);
				$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");
				$amtTotal=0;
				$strMsg="";
				$strSQL="BEGIN ";
				foreach($_REQUEST["txtTenderID"] as $k=>$v)
				{
					if($v=="") continue;
					$amt=0;
					$strTmp=explode(":",$v);
					foreach($tenders as $kk=>$vv)
					{
						if($vv->tenderId==$strTmp[0]) 
						{
							$amt = floatval($vv->guaranteeAmount);
							$amtTotal+=$amt;
							$strMsg.="\n".$vv->tenderCode." - ".$vv->tenderName.": ".number_format(floatval($amt),2);
							break;
						}
					}
					
					$strSQL.="insert into etender (invitationnumber,refno, amendno, amount, tenderno, 
								tendername, tenderid, tenderorgname, tenderorgid, tendertype, tenderonline, tenderstartdate, 
								orgname1, orgid1, orgid2, orgceoname, orgopendate, orgclasscode, orgaddrs, orgpostcode, orgphone, orgfax, orgmail, orgregdate, 
								comments, typecode, custno, custno1, 
								bankname, bankid, regdate, status, regempid, regbrchno,orgtenderdata) 
						SELECT '".@$_REQUEST["tenInvitationNumber"]."','".@$_REQUEST['txtRefNo']."','".@$_REQUEST['txtAmendNo']."', '".@$amt."', '".@$SndTransmission_Number."-".$k."', 
							'".@$_REQUEST['txtTenderName']."', '".@$v."', '".@$_REQUEST['txtOrgName']."', '".@$_REQUEST['txtOrgID']."', 'Y', 'Y', to_date('".@$_REQUEST['txtStartDate']."','YYYY-MM-DD HH24:MI'),
							'".@$_REQUEST['txtOrgName1']."', '".@$_REQUEST['txtOrgID1']."', C.ID2,C.DIRNAME, C.birthdate, C.orgtypecode, PKG_FUNCTIONS.GETCUSTADDRESS(c.custno,3,','), C.POSTADDR, C.WORKPHONE, C.FAX, C.EMAIL, C.REGDATE,
							'".@$_REQUEST['txtComment']."', '".@$_REQUEST['selTypeCode']."', '".@$_REQUEST['txtOrgRetailCustNo']."', '".@$_REQUEST['txtOrgCustNo']."', 
							'".$gHeader['SenderName']."', '".$gHeader['SenderID']."', sysdate, 1,".@$_SESSION['EMPID'].",'".@$_SESSION['BRANCHNO']."', '".@$_REQUEST['tenOrgTenderData']."' 
						FROM gb.cust c
						WHERE trim(c.id1)='".@$_REQUEST['txtOrgID1']."' AND c.statuscode<>'0'; ";
				}
				if(trim($amtTotal) > trim($_REQUEST['txtAmt']))
				{
					$strMsg="Баталгааны дүн хүрэлцэхгүй байна!\nИлгээж байгаа мэдээллээ шалгаад, дахин илгээнэ үү!".$amtTotal."-".@$_REQUEST['txtAmt']."\n------------------------------------------------------".$strMsg;
					$strMsg.="\n------------------------------------------------------\n";
					$strMsg.="Нийт дүн: ".number_format($amtTotal,3);
					$strMsg.="\n------------------------------------------------------\n";
					$strMsg.="Баталгааны дүн: ".number_format($_REQUEST["txtAmt"],3)."\n";
					$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
					echo json_encode(array("error"=>"1","msg"=>$strMsg, "sql"=>$strSQL));
					break;
				}
				$strSQL.=" END;";
			}#End Нэг баталгаатай багц тендер
			else 
				$strSQL="insert into etender (invitationnumber,refno, amendno, amount, tenderno, 
							tendername, tenderid, tenderorgname, tenderorgid, tendertype, tenderonline, tenderstartdate, 
							orgname1, orgid1, orgid2, orgceoname, orgopendate, orgclasscode, orgaddrs, orgpostcode, orgphone, orgfax, orgmail, orgregdate, 
							comments, typecode, custno, custno1, 
							bankname, bankid, regdate, status, regempid, regbrchno,orgtenderdata) 
					SELECT '".@$_REQUEST["tenInvitationNumber"]."','".@$_REQUEST['txtRefNo']."','".@$_REQUEST['txtAmendNo']."', '".@$_REQUEST['txtAmt']."', '".@$SndTransmission_Number."', 
						'".str_replace("'","",@$_REQUEST['txtTenderName'])."', '".@$_REQUEST['txtTenderID']."', '".@$_REQUEST['txtOrgName']."', '".@$_REQUEST['txtOrgID']."', 'Y', 'Y', to_date('".@$_REQUEST['txtStartDate']."','YYYY-MM-DD HH24:MI'), 
						'".@$_REQUEST['txtOrgName1']."', '".@$_REQUEST['txtOrgID1']."', C.ID2,C.DIRNAME, C.birthdate, C.orgtypecode, PKG_FUNCTIONS.GETCUSTADDRESS(c.custno,3,','), C.POSTADDR, C.WORKPHONE, C.FAX, C.EMAIL, C.REGDATE,
						'".@$_REQUEST['txtComment']."', '".@$_REQUEST['selTypeCode']."', '".@$_REQUEST['txtOrgRetailCustNo']."', '".@$_REQUEST['txtOrgCustNo']."', 
						'".$gHeader['SenderName']."', '".$gHeader['SenderID']."', sysdate, 1,".@$_SESSION['EMPID'].",'".@$_SESSION['BRANCHNO']."', '".@$_REQUEST['tenOrgTenderData']."' 
					FROM gb.cust c
					WHERE trim(c.id1)='".@$_REQUEST['txtOrgID1']."' AND c.statuscode<>'0'";
				
			$result=@ociparse($connectiongb,$strSQL);
			if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
			{
				$err=ocierror($result);
				fnWrite2Log("ETENDER", "SQL ERROR", $err["message"]." -> \n".@$strSQL);
				$strMsg="Баталгааны бичилтийг хийж чадсангүй алдаа гарлаа\n";
				$strMsg.="Илгээж байгаа мэдээллээ шалгаад, дахин илгээнэ үү!\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
				echo json_encode(array("error"=>"1","msg"=>$strMsg.$strSQL));
				break;
			}

			if(@$_REQUEST['tenGuaranteeID']!="") 
			{ #Begin Баталгаа засах
				$xmlDom = new DomDocument('1.0','UTF-8');
				$xmlDom->formatOutput = true;
				
				//Header Setting
				$xmlEnvelope = $xmlDom->appendChild($xmlDom->createElement('soap-env:Envelope')); 
				$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
				$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
				$xmlBody = $xmlEnvelope->appendChild($xmlDom->createElement('soap-env:Body')); 
				$xmlAction = $xmlBody->appendChild($xmlDom->createElement("web:".@$gDoc[8][0])); 
			
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('username')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["username"])); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('password')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["password"])); 
				
				$strData=gzuncompress(base64_decode(@$_REQUEST["tenGuaranteeID"]));
				$strData=str_replace("S:","",$strData);
				$strData=str_replace("ns2:","",$strData);
				$strDataXml = @new SimpleXMLElement($strData);
				$tenders=$strDataXml->xpath("/Envelope/Body/getGuaranteeInfoResponse/return/guaranteeInfo");
				foreach($tenders as $k=>$v)
				{
					$strTmp=explode(":",$_REQUEST["txtTenderID"]);
					if($v->tenderId==$strTmp[0]) 
					{
						$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeId')); 
						$xmlItm->appendChild($xmlDom->createTextNode($v->guaranteeId)); 
						break;
					}
				}
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeBankXml')); 
				$xmlItm = $xmlItm->appendChild($xmlDom->createCDATASection("<GuaranteeBank>
						<Tender>
							<GuaranteeTypeCode>".@$_REQUEST["selTypeCode"]."</GuaranteeTypeCode>
							<CurrencyCode>".@$_REQUEST["selCurCode"]."</CurrencyCode>
							<GuaranteeAmount>".@$_REQUEST["txtAmt"]."</GuaranteeAmount>
							<GuaranteeNumber>".@$_REQUEST["txtRefNo"].@$_REQUEST["txtAmendNo"]."</GuaranteeNumber>
							<GuaranteeStartDate>".str_replace(".","-",@$_REQUEST["txtStartDate"])."</GuaranteeStartDate>
							<GuaranteeEndDate>".str_replace(".","-",@$_REQUEST["txtStartDate"])."</GuaranteeEndDate>
							<Description>".@$_REQUEST["txtComment"]."</Description>
						</Tender>
					</GuaranteeBank>"));

				$Soap = $xmlDom->saveXML();
				fnWrite2Log("ETENDER", "Request: updateGuarantee", $Soap);

				$Service = new Webservice($gUrl, "SOAP", "utf-8");
				flush();
				$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
				fnWrite2Log("ETENDER", "Response: updateGuarantee", @$Response["Body"]);
				if($Response["Status"]!=200)
				{
					echo json_encode(array("error"=>"1","msg"=>"Баталгааг илгээж чадсангүй холболтын алдаа гарлаа!\nТа дахин баталгааны мэдээллийг илгээнэ үү."));
					break;
				}
				$strData=str_replace("S:","",$Response["Body"]);
				$strData=str_replace("ns2:","",$strData);
				try {
					$xml = @new SimpleXMLElement($strData);
				}
				catch(Exception $e){
					print_r($e);
					break;
				}
				//Common Header Part
				$Receive_Time = date("Y/m/d h:i:s");
				$Result_Code = $xml->Body->updateGuaranteeResponse->return->responseResultType;
				if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->updateGuaranteeResponse->return->failureMessages->message->failureCode!="") 
				{
					$Result_Code = $xml->Body->updateGuaranteeResponse->return->failureMessages->message->failureCode;
					$Result_Message = $xml->Body->updateGuaranteeResponse->return->description."\n";
					foreach($xml->Body->updateGuaranteeResponse->return->failureMessages->message as $k=>$v)
						$Result_Message .= $v->failureCode.". ".$v->failureMessage."\n";
				}
				$strSQL="UPDATE etender SET 
							receivetime=to_date('".$Receive_Time."','YYYY-MM-DD HH24:MI:SS'), 
							resultcode='".$Result_Code."', 
							resultmessage='".substr($Result_Message,0,2000)."'
						WHERE case when tenderno like '%-%' then substr(tenderno,0,instr(tenderno,'-')-1) else tenderno end='".$SndTransmission_Number."'";
				$result=@ociparse($connectiongb,$strSQL);
				if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
				{
					$err=ocierror($result);
					fnWrite2Log("ETENDER", "SQL ERROR", $err["message"]." -> \n".@$strSQL);
				}
				
				//Success
				if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->updateGuaranteeResponse->return->failureMessages->message->failureCode!="") 
				{
					$strMsg="Баталгааг засахад алдаа гарлаа!\n";
					$strMsg.="Баталгааг шалгаад дахин илгээнэ үү!\n";
					$strMsg.="------------------------------------------------------\n";
					$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";

					$strMsg.="Алдааны код:\n".$Result_Code."\n";
					$strMsg.="Алдааны мэдээлэл:\n".$Result_Message;
					echo json_encode(array("error"=>"1","msg"=>$strMsg));
					break;
				}
	
				$strMsg="Баталгаа амжилттай засагдлаа\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
	
				$strMsg.="Хүлээн авсан огноо: ".$Receive_Time."\n";
				$strMsg.="Хариу код: ".$Result_Code."\n";
				$Result_Message = $xml->Body->updateGuaranteeResponse->return->description;
				$strMsg.="Хариу мессеж: ".$Result_Message;
				echo json_encode(array("error"=>"0","msg"=>$strMsg,"id"=>$SndTransmission_Number));
				break;
			} #End Баталгаа засах
			
			$xmlTenders="";
			if(is_array($_REQUEST["txtTenderID"]))
			{ #Begin Нэг баталгаатай багц тендер
				$strData=gzuncompress(base64_decode(@$_REQUEST["tenOrgTenderData"]));
				$strData=str_replace("S:","",$strData);
				$strData=str_replace("ns2:","",$strData);
				$xml = @new SimpleXMLElement($strData);
				$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");
				foreach($_REQUEST["txtTenderID"] as $k=>$v)
				{
					if($v=="") continue;
					$amt=0;
					$strTmp=explode(":",$v);
					foreach($tenders as $kk=>$vv)
					{
						if($vv->tenderId==$strTmp[0]) 
						{
							$amt = floatval($vv->guaranteeAmount);
							break;
						}
					}
				
					$xmlTenders.="<Tender>
								<TenderId>".@$strTmp[0]."</TenderId>
								<GuaranteeTypeCode>".@$_REQUEST["selTypeCode"]."</GuaranteeTypeCode>
								<CurrencyCode>".@$_REQUEST["selCurCode"]."</CurrencyCode>
								<GuaranteeAmount>".@$amt."</GuaranteeAmount>
								<GuaranteeNumber>".@$_REQUEST["txtRefNo"].@$_REQUEST["txtAmendNo"]."</GuaranteeNumber>
								<GuaranteeStartDate>".str_replace(".","-",@$_REQUEST["txtStartDate"])."</GuaranteeStartDate>
								<GuaranteeEndDate>".str_replace(".","-",@$_REQUEST["txtStartDate"])."</GuaranteeEndDate>
								<Description>".@$_REQUEST["txtComment"]."</Description>
							</Tender>";			
				}
			} #End Нэг баталгаатай багц тендер
			else
			{
				$strTmp=explode(":",@$_REQUEST["txtTenderID"]);
				$xmlTenders="<Tender>
							<TenderId>".@$strTmp[0]."</TenderId>
							<GuaranteeTypeCode>".@$_REQUEST["selTypeCode"]."</GuaranteeTypeCode>
							<CurrencyCode>".@$_REQUEST["selCurCode"]."</CurrencyCode>
							<GuaranteeAmount>".@$_REQUEST["txtAmt"]."</GuaranteeAmount>
							<GuaranteeNumber>".@$_REQUEST["txtRefNo"].@$_REQUEST["txtAmendNo"]."</GuaranteeNumber>
							<GuaranteeStartDate>".str_replace(".","-",@$_REQUEST["txtStartDate"])."</GuaranteeStartDate>
							<GuaranteeEndDate>".str_replace(".","-",@$_REQUEST["txtStartDate"])."</GuaranteeEndDate>
							<Description>".@$_REQUEST["txtComment"]."</Description>
						</Tender>";			
			}
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeBankXml')); 
			$xmlItm = $xmlItm->appendChild($xmlDom->createCDATASection("<GuaranteeBank>
					<InvitationNumber>".@$_REQUEST["tenInvitationNumber"]."</InvitationNumber>
					<ClientRegisterNumber>".@$_REQUEST["txtOrgID"]."</ClientRegisterNumber>
					<BidderRegisterNumber>".@$_REQUEST["txtOrgID1"]."</BidderRegisterNumber>
					<SupplierTypeCode>".@$_REQUEST["tenSupplierTypeCode"]."</SupplierTypeCode>
					<GuaranteeTypeCode>".@$_REQUEST["selTypeCode"]."</GuaranteeTypeCode>
					<Description>".@$_REQUEST["txtComment"]."</Description>

					<BankRegisterNumber>".@$_REQUEST["txtBankID"]."</BankRegisterNumber>
					<BankCode>".$gCommon["BankCode"]."</BankCode>
					<BankDirectorName>".@$_REQUEST["txtBrchDir"]."</BankDirectorName>
					<BankEmployeeName>".@$_REQUEST["txtEmp"]."</BankEmployeeName>
					<BankEmployeePhone>".@$_REQUEST["txtEmpPhone"]."</BankEmployeePhone>
					<BankEmployeeEmail>".@$_REQUEST["txtEmpMail"]."</BankEmployeeEmail>
					<BankEmployeeFax>".@$_REQUEST["txtEmpFax"]."</BankEmployeeFax>

					".$xmlTenders."
			</GuaranteeBank>"));
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: GuaranteeBank", $Soap);
			$Service = new Webservice($gUrl, "SOAP", "utf-8");

			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
			fnWrite2Log("ETENDER", "Response: GuaranteeBank", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo json_encode(array("error"=>"1","msg"=>"Баталгааг илгээж чадсангүй холболтын алдаа гарлаа!\nТа дахин баталгааны мэдээллийг илгээнэ үү."));
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->guaranteeBankResponse->return->responseResultType;
			fnWrite2Log("ETENDER", "XML:::",$xml->Body->guaranteeBankResponse);
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->guaranteeBankResponse->return->failureMessages->message->failureCode!="") 
			{
				$Result_Code = $xml->Body->guaranteeBankResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->guaranteeBankResponse->return->description."\n";
				foreach($xml->Body->guaranteeBankResponse->return->failureMessages->message as $k=>$v)
					$Result_Message .= $v->failureCode.". ".$v->failureMessage."\n";
			}
			$strSQL="UPDATE etender SET 
						receivetime=to_date('".$Receive_Time."','YYYY-MM-DD HH24:MI:SS'), 
						resultcode='".$Result_Code."', 
						resultmessage='".substr($Result_Message,0,2000)."'
					WHERE case when tenderno like '%-%' then substr(tenderno,0,instr(tenderno,'-')-1) else tenderno end='".$SndTransmission_Number."'";
			$result=@ociparse($connectiongb,$strSQL);
			if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
			{
				$err=ocierror($result);
				fnWrite2Log("ETENDER", "SQL ERROR", $err["message"]." -> \n".@$strSQL);
			}
			
			//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->guaranteeBankResponse->return->failureMessages->message->failureCode!="") 
			{
				$strMsg="Баталгааг илгээхэд алдаа гарлаа!\n";
				$strMsg.="Баталгааг шалгаад дахин илгээнэ үү!\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";

				$strMsg.="Алдааны код: ".$Result_Code."\n";
				$strMsg.="Алдааны мэдээлэл:\n".$Result_Message;
				echo json_encode(array("error"=>"1","msg"=>$strMsg));
				break;
			}

			$strMsg="Баталгаа амжилттай илгээгдлээ\n";
			$strMsg.="------------------------------------------------------\n";
			$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";

			$strMsg.="Хүлээн авсан огноо: ".$Receive_Time."\n";
			$strMsg.="Хариу код: ".$Result_Code."\n";
			$Result_Message = $xml->Body->guaranteeBankResponse->return->description;
			$strMsg.="Хариу мессеж: ".$Result_Message;
			echo json_encode(array("error"=>"0","msg"=>$strMsg,"id"=>$SndTransmission_Number));
			break;
		case "10": #Баталгаа гаргах
			if(@$_REQUEST['tenGuaranteeIDga']!="") 
			{ #Begin Засвар орж буй хуучин баталгааг идэвхигүй болгох
				$strSQL="UPDATE etendergadaad SET status='0',
							receivetime1=sysdate,
							resultmessage1='Засагдсан',
							resultcode1='SUCCESS',
							transmissionid1='".$SndTransmission_Number."'
						WHERE custno='".@$_REQUEST['txtOrgRetailCustNoGa']."'
							AND invitationnumber='".@$_REQUEST['tenInvitationNumberga']."' 
							AND tenderid='".@$_REQUEST['txtTenderIDGa']."'
							AND status=1";
				fnWrite2Log("ETENDER", "UPDATE", @$strSQL);
				$result=@ociparse($connectiongb,$strSQL);
				if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
				{
					$strMsg="Өөрчлөлт хийх баталгааны мэдээлэл олдсонгүй\n";
					$strMsg.="Та тендерийн мэдээллийг шалгана уу!\n";
					$strMsg.="------------------------------------------------------\n";
					$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
					echo json_encode(array("error"=>"1","msg"=>$strMsg));
					break;
				}
			} #End Засвар орж буй хуучин баталгааг идэвхигүй болгох
			
			if(is_array($_REQUEST["txtTenderIDGa"]))
			{ #Begin Нэг баталгаатай багц тендер
				$strData=gzuncompress(base64_decode(@$_REQUEST["tenOrgTenderDataga"]));
				$strData=str_replace("S:","",$strData);
				$strData=str_replace("ns2:","",$strData);
				$xml = @new SimpleXMLElement($strData);
				$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");
				$amtTotal=0;
				$strMsg="";

				foreach($_REQUEST["txtTenderIDGa"] as $k=>$v)
				{
					if($v=="") continue;
					$amt=0;
					$strTmp=explode(":",$v);
					foreach($tenders as $kk=>$vv)
					{
						if($vv->tenderId==$strTmp[0]) 
						{
							$amt = floatval($vv->guaranteeAmount);
							$amtTotal+=$amt;
							$strMsg.="\n".$vv->tenderCode." - ".$vv->tenderName.": ".number_format(floatval($amt),2);
							break;
						}
					}
					
					

					$strSQL = "insert into etendergadaad (invitationnumber,refno ,amendno, amount,tenderno, 
															tendername, tenderid, tenderorgname, tenderorgid, tendertype, tenderonline, tenderstartdate, 
															orgname1, orgid1, orgid2, orgceoname, typecode, custno, custno1, 
															bankname, bankid, regdate, status, regempid, regbrchno)
															
														VALUES('".@$_REQUEST["txtTenderInvGa"]."','".@$_REQUEST["batalgaanumberga"]."', '00', '".@$_REQUEST["txtAmtGa"]."', '".@$SndTransmission_Number."',
														'".str_replace("'","",@$_REQUEST['txtTenderNameGa'])."', '".@$_REQUEST['txtTenderIDGa']."', '".@$_REQUEST['txtOrgNameGa']."','".@$_REQUEST['txtOrgIDGa']."',
														'Y', 'Y', to_date('".@$_REQUEST['txtStartDateGa']."','YYYY-MM-DD HH24:MI'), '".@$_REQUEST['txtOrgName1Ga']."','".@$_REQUEST['txtOrgID1Ga']."',
														' ', '".@$_REQUEST['txtBrchDirGa']."','".@$_REQUEST['selTypeCodeGa']."','".@$_REQUEST['txtOrgRetailCustNoGa']."', ' ', '".@$_REQUEST['txtBankNameGa']."','".@$_REQUEST['txtBankIDGa']."',
														sysdate, '1', ".@$_SESSION['EMPID'].",'".@$_SESSION['BRANCHNO']."')";
				}
				
	
			}#End Нэг баталгаатай багц тендер
			else 
			$strSQL = "insert into etendergadaad (invitationnumber,refno ,amendno, amount,tenderno, 
			tendername, tenderid, tenderorgname, tenderorgid, tendertype, tenderonline, tenderstartdate, 
			orgname1, orgid1, orgid2, orgceoname, typecode, custno, custno1, 
			bankname, bankid, regdate, status, regempid, regbrchno)
			
						VALUES('".@$_REQUEST["txtTenderInvGa"]."','".@$_REQUEST["batalgaanumberga"]."', '00', '".@$_REQUEST["txtAmtGa"]."', '".@$SndTransmission_Number."',
						'".str_replace("'","",@$_REQUEST['txtTenderNameGa'])."', '".@$_REQUEST['txtTenderIDGa']."', '".@$_REQUEST['txtOrgNameGa']."','".@$_REQUEST['txtOrgIDGa']."',
						'Y', 'Y', to_date('".@$_REQUEST['txtStartDateGa']."','YYYY-MM-DD HH24:MI'), '".@$_REQUEST['txtOrgName1Ga']."','".@$_REQUEST['txtOrgID1Ga']."',
						' ', '".@$_REQUEST['txtBrchDirGa']."','".@$_REQUEST['selTypeCodeGa']."','".@$_REQUEST['txtOrgRetailCustNoGa']."', ' ', '".@$_REQUEST['txtBankNameGa']."','".@$_REQUEST['txtBankIDGa']."',
						sysdate, '1', ".@$_SESSION['EMPID'].",'".@$_SESSION['BRANCHNO']."')";
				
			$result=@ociparse($connectiongb,$strSQL);
			if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
			{
				$err=ocierror($result);
				fnWrite2Log("ETENDER", "SQL ERROR", $err["message"]." -> \n".@$strSQL);
				$strMsg="Баталгааны бичилтийг хийж чадсангүй алдаа гарлаа\n";
				$strMsg.="Илгээж байгаа мэдээллээ шалгаад, дахин илгээнэ үү!\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
				echo json_encode(array("error"=>"1","msg"=>$strMsg.$strSQL));
				break;
			}

			if(@$_REQUEST['tenGuaranteeIDga']!="") 
			{ #Begin Баталгаа засах
				$xmlDom = new DomDocument('1.0','UTF-8');
				$xmlDom->formatOutput = true;
				
				//Header Setting
				$xmlEnvelope = $xmlDom->appendChild($xmlDom->createElement('soap-env:Envelope')); 
				$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
				$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
				$xmlBody = $xmlEnvelope->appendChild($xmlDom->createElement('soap-env:Body')); 
				$xmlAction = $xmlBody->appendChild($xmlDom->createElement("web:".@$gDoc[8][0])); 
			
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('username')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["username"])); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('password')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["password"])); 
				
				$strData=gzuncompress(base64_decode(@$_REQUEST["tenGuaranteeIDga"]));
				$strData=str_replace("S:","",$strData);
				$strData=str_replace("ns2:","",$strData);
				$strDataXml = @new SimpleXMLElement($strData);
				$tenders=$strDataXml->xpath("/Envelope/Body/getGuaranteeInfoResponse/return/guaranteeInfo");
				foreach($tenders as $k=>$v)
				{
					$strTmp=explode(":",$_REQUEST["txtTenderIDGa"]);
					if($v->tenderId==$strTmp[0]) 
					{
						$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeId')); 
						$xmlItm->appendChild($xmlDom->createTextNode($v->guaranteeId)); 
						break;
					}
				}
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeBankXml')); 
				$xmlItm = $xmlItm->appendChild($xmlDom->createCDATASection("<GuaranteeBank>
						<Tender>
							<GuaranteeTypeCode>".@$_REQUEST["selTypeCodeGa"]."</GuaranteeTypeCode>
							<CurrencyCode>".@$_REQUEST["selCurCodeGa"]."</CurrencyCode>
							<GuaranteeAmount>".@$_REQUEST["txtAmtGa"]."</GuaranteeAmount>
							<GuaranteeNumber>".@$_REQUEST["batalgaanumberga"].@$_REQUEST["txtAmendNoga"]."</GuaranteeNumber>
							<GuaranteeStartDate>".str_replace(".","-",@$_REQUEST["txtStartDateGa"])."</GuaranteeStartDate>
							<GuaranteeEndDate>".str_replace(".","-",@$_REQUEST["txtEndDateGa"])."</GuaranteeEndDate>
							<Description>".@$_REQUEST["txtCommentGa"]."</Description>
						</Tender>
					</GuaranteeBank>"));

				$Soap = $xmlDom->saveXML();
				fnWrite2Log("ETENDER", "Request: updateGuarantee", $Soap);

				$Service = new Webservice($gUrl, "SOAP", "utf-8");
				flush();
				$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
				fnWrite2Log("ETENDER", "Response: updateGuarantee", @$Response["Body"]);
				if($Response["Status"]!=200)
				{
					echo json_encode(array("error"=>"1","msg"=>"Баталгааг илгээж чадсангүй холболтын алдаа гарлаа!\nТа дахин баталгааны мэдээллийг илгээнэ үү."));
					break;
				}
				$strData=str_replace("S:","",$Response["Body"]);
				$strData=str_replace("ns2:","",$strData);
				try {
					$xml = @new SimpleXMLElement($strData);
				}
				catch(Exception $e){
					print_r($e);
					break;
				}
				//Common Header Part
				$Receive_Time = date("Y/m/d h:i:s");
				$Result_Code = $xml->Body->updateGuaranteeResponse->return->responseResultType;
				if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->updateGuaranteeResponse->return->failureMessages->message->failureCode!="") 
				{
					$Result_Code = $xml->Body->updateGuaranteeResponse->return->failureMessages->message->failureCode;
					$Result_Message = $xml->Body->updateGuaranteeResponse->return->description."\n";
					foreach($xml->Body->updateGuaranteeResponse->return->failureMessages->message as $k=>$v)
						$Result_Message .= $v->failureCode.". ".$v->failureMessage."\n";
				}
				$strSQL="UPDATE etendergadaad SET 
							receivetime=to_date('".$Receive_Time."','YYYY-MM-DD HH24:MI:SS'), 
							resultcode='".$Result_Code."', 
							resultmessage='".substr($Result_Message,0,2000)."'
						WHERE case when tenderno like '%-%' then substr(tenderno,0,instr(tenderno,'-')-1) else tenderno end='".$SndTransmission_Number."'";
				$result=@ociparse($connectiongb,$strSQL);
				if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
				{
					$err=ocierror($result);
					fnWrite2Log("ETENDER", "SQL ERROR", $err["message"]." -> \n".@$strSQL);
				}
				
				//Success
				if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->updateGuaranteeResponse->return->failureMessages->message->failureCode!="") 
				{
					$strMsg="Баталгааг засахад алдаа гарлаа!\n";
					$strMsg.="Баталгааг шалгаад дахин илгээнэ үү!\n";
					$strMsg.="------------------------------------------------------\n";
					$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";

					$strMsg.="Алдааны код:\n".$Result_Code."\n";
					$strMsg.="Алдааны мэдээлэл:\n".$Result_Message;
					echo json_encode(array("error"=>"1","msg"=>$strMsg));
					break;
				}
	
				$strMsg="Баталгаа амжилттай засагдлаа\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
	
				$strMsg.="Хүлээн авсан огноо: ".$Receive_Time."\n";
				$strMsg.="Хариу код: ".$Result_Code."\n";
				$Result_Message = $xml->Body->updateGuaranteeResponse->return->description;
				$strMsg.="Хариу мессеж: ".$Result_Message;
				echo json_encode(array("error"=>"0","msg"=>$strMsg,"id"=>$SndTransmission_Number));
				break;
			} #End Баталгаа засах
			
			$xmlTenders="";
			if(is_array($_REQUEST["txtTenderIDGa"]))
			{ #Begin Нэг баталгаатай багц тендер
				$strData=gzuncompress(base64_decode(@$_REQUEST["tenOrgTenderDataga"]));
				$strData=str_replace("S:","",$strData);
				$strData=str_replace("ns2:","",$strData);
				$xml = @new SimpleXMLElement($strData);
				$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");
				foreach($_REQUEST["txtTenderIDGa"] as $k=>$v)
				{
					if($v=="") continue;
					$amt=0;
					$strTmp=explode(":",$v);
					foreach($tenders as $kk=>$vv)
					{
						if($vv->tenderId==$strTmp[0]) 
						{
							$amt = floatval($vv->guaranteeAmount);
							break;
						}
					}
				
					$xmlTenders.="<Tender>
								<TenderId>".@$strTmp[0]."</TenderId>
								<GuaranteeTypeCode>".@$_REQUEST["selTypeCodeGa"]."</GuaranteeTypeCode>
								<CurrencyCode>".@$_REQUEST["selCurCodeGa"]."</CurrencyCode>
								<GuaranteeAmount>".@$amt."</GuaranteeAmount>
								<GuaranteeNumber>".@$_REQUEST["batalgaanumberga"]."</GuaranteeNumber>
								<GuaranteeStartDate>".str_replace(".","-",@$_REQUEST["txtStartDateGa"])."</GuaranteeStartDate>
								<Description>".@$_REQUEST["txtCommentGa"]."</Description>
							</Tender>";		
				}
			} #End Нэг баталгаатай багц тендер
			else
			{
				$strTmp=explode(":",@$_REQUEST["txtTenderIDGa"]);
				$xmlTenders="<Tender>
							<TenderId>".@$strTmp[0]."</TenderId>
							<GuaranteeTypeCode>".@$_REQUEST["selTypeCodeGa"]."</GuaranteeTypeCode>
							<CurrencyCode>".@$_REQUEST["selCurCodeGa"]."</CurrencyCode>
							<GuaranteeAmount>".@$_REQUEST["txtAmtGa"]."</GuaranteeAmount>
							<GuaranteeNumber>".@$_REQUEST["batalgaanumberga"]."</GuaranteeNumber>
							<GuaranteeStartDate>".str_replace(".","-",@$_REQUEST["txtStartDateGa"])."</GuaranteeStartDate>
							<Description>".@$_REQUEST["txtCommentGa"]."</Description>
						</Tender>";		
			}
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeBankXml')); 
			$xmlItm = $xmlItm->appendChild($xmlDom->createCDATASection("<GuaranteeBank>
					<InvitationNumber>".@$_REQUEST["txtTenderInvGa"]."</InvitationNumber>
					<ClientRegisterNumber>".@$_REQUEST["txtOrgIDGa"]."</ClientRegisterNumber>
					<BidderRegisterNumber>".@$_REQUEST["txtOrgID1Ga"]."</BidderRegisterNumber>
					<SupplierTypeCode>"."ORGANIZATION"."</SupplierTypeCode> 
					<GuaranteeTypeCode>".@$_REQUEST["selTypeCodeGa"]."</GuaranteeTypeCode>
					<Description>".@$_REQUEST["txtCommentGa"]."</Description>

					<BankRegisterNumber>".@$_REQUEST["txtBankIDGa"]."</BankRegisterNumber>
					<BankCode>".$_REQUEST["txtBankIDcodeGa"]."</BankCode>
					<BankDirectorName>".@$_REQUEST["txtBrchDirGa"]."</BankDirectorName>
					<BankEmployeeName>".@$_REQUEST["txtEmpGa"]."</BankEmployeeName>
					<BankEmployeePhone>".@$_REQUEST["txtEmpPhoneGa"]."</BankEmployeePhone>
					<BankEmployeeEmail>".@$_REQUEST["txtEmpMailGa"]."</BankEmployeeEmail>
					
					".$xmlTenders."
					<RegisteredBank>"."Худалдаа Хөгжлийн Банк"."</RegisteredBank>
					<RegisteredBankAddress>"."Монгол улс, Улаанбаатар хот, Жуулчны гудамж 7, Бага тойруу 12"."</RegisteredBankAddress>
					<RegisteredBankContact>"."312726"."</RegisteredBankContact>
					<RegisteredBankEmployee>".@$_REQUEST["Obankempnamega"]."</RegisteredBankEmployee>

			</GuaranteeBank>"));
			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: GuaranteeBank", $Soap);
			$Service = new Webservice($gUrl, "SOAP", "utf-8");

			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), $Action);
			fnWrite2Log("ETENDER", "Response: GuaranteeBank", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo json_encode(array("error"=>"1","msg"=>"Баталгааг илгээж чадсангүй холболтын алдаа гарлаа!\nТа дахин баталгааны мэдээллийг илгээнэ үү."));
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
			//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->guaranteeBankResponse->return->responseResultType;
			fnWrite2Log("ETENDER", "XML:::",$xml->Body->guaranteeBankResponse);
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->guaranteeBankResponse->return->failureMessages->message->failureCode!="") 
			{
				$Result_Code = $xml->Body->guaranteeBankResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->guaranteeBankResponse->return->description."\n";
				foreach($xml->Body->guaranteeBankResponse->return->failureMessages->message as $k=>$v)
					$Result_Message .= $v->failureCode.". ".$v->failureMessage."\n";
			}
			
			
			//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->guaranteeBankResponse->return->failureMessages->message->failureCode!="") 
			{
				$strMsg="Баталгааг илгээхэд алдаа гарлаа!\n";
				$strMsg.="Баталгааг шалгаад дахин илгээнэ үү!\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";

				$strMsg.="Алдааны код: ".$Result_Code."\n";
				$strMsg.="Алдааны мэдээлэл:\n".$Result_Message;
				echo json_encode(array("error"=>"1","msg"=>$strMsg));
				break;
			}

			$strMsg="Баталгаа амжилттай илгээгдлээ\n";
			$strMsg.="------------------------------------------------------\n";
			$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";

			$strMsg.="Хүлээн авсан огноо: ".$Receive_Time."\n";
			$strMsg.="Хариу код: ".$Result_Code."\n";
			$Result_Message = $xml->Body->guaranteeBankResponse->return->description;
			$strMsg.="Хариу мессеж: ".$Result_Message;
			echo json_encode(array("error"=>"0","msg"=>$strMsg,"id"=>$SndTransmission_Number));
			break;
		case "3": #Тендерийн мэдээлэл
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('invitationNumber')); 
			$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtNumberID']))); 
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('bidderRegisterNumber')); 
			$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtID']))); 
			$xmlItm = $xmlAction->appendChild($xmlDom->createElement('supplierTypeCode')); 
			$xmlItm->appendChild($xmlDom->createTextNode("ORGANIZATION")); // Roperter : Мөнхжаргал.баа утсаар асуусан болно. 2024.07.22 03:40

		
			$strGuaranteeInfo="";
			$gGuaranteeID="";
			
			if($_REQUEST["selTypega"]!="")
			{
				$strSQL="SELECT t.tenderno
						   FROM etendergadaad t
						  WHERE orgid1='".@$_REQUEST['txtID']."'
							AND invitationnumber='".trim(@$_REQUEST['txtNumberID'])."' 
							AND status=1";
				$result=@ociparse($connectiongbsb,$strSQL);		
				@ociexecute($result,OCI_DEFAULT);
				$recTender=array();
				ocifetchinto($result,$recTender,OCI_ASSOC);				
				
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('guaranteeTypeCode')); 
				$xmlItm->appendChild($xmlDom->createTextNode(@$_REQUEST["selTypega"]));
				$Soap = $xmlDom->saveXML();
				fnWrite2Log("ETENDER", "Request: soap", $Soap);
				
				$Service = new Webservice($gUrl, "SOAP", "utf-8");

				flush();
				$Response = $Service->SendRequest(str_replace("\'","'",$Soap), @$Action);
				fnWrite2Log("ETENDER", "Response: response body", @$Response["Body"]);
				$guaranteeIdForCancel = @$Response["Body"];
				if($Response["Status"]!=200)
				{
					echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
					print_r($Response);
					echo "</p>";
					if($_SESSION['EMPID'] == 27350){
						echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
						print_r($Soap);
						echo "</p>";
					}
					break;
				}
				$strData=str_replace("S:","",$Response["Body"]);
				$strData=str_replace("ns2:","",$strData);
				try {
					$xml = @new SimpleXMLElement($strData);
				}
				catch(Exception $e){
					print_r($e);
					break;
				}
				//Common Header Part
				$Receive_Time = date("Y/m/d h:i:s");
				$Result_Code = $xml->Body->getGuaranteeInfoResponse->return->responseResultType;

				//Success
				if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getGuaranteeInfoResponse->return->failureMessages->message->failureCode!="")
				{
					echo "<p style=\"color:red\"><br>Fail !!!";
					$Result_Code = $xml->Body->getGuaranteeInfoResponse->return->failureMessages->message->failureCode;
					$Result_Message = $xml->Body->getGuaranteeInfoResponse->return->description."<br>";
					foreach($xml->Body->getGuaranteeInfoResponse->return->failureMessages->message as $k=>$v)
						$Result_Message .= $v->failureCode.". ".$v->failureMessage."<br>";
					
					echo "<br>Алдааны код: ".$Result_Code;
					echo "<br>Алдааны мэдээлэл: ".$Result_Message,"</p>";
					break;
				}
				#Begin Банкнаас илгээсэн харилцагчийн баталгаанууд
				$gGuaranteeID=base64_encode(gzcompress($strData,9));
				ob_start();
				?>
                  <tr>
                    <td colspan="2" style="background-color:green; color:white" title="Банкнаас Цахим тендерийн системд илгээсэн байгаа баталгаанууд"><strong>II. Тендерийн бүртгэлийн мэдээлэл (Банкнаас Цахим тендерийн системд илгээсэн байгаа баталгаанууд)</strong></td>
                  </tr>
                  <tr>
                    <td colspan="2">
                    <table title="Банкнаас Цахим тендерийн системд илгээсэн байгаа баталгаанууд">
                    	<tr>
							<?
                            foreach($xml->Body->getGuaranteeInfoResponse->return->guaranteeInfo as $key=>$val)
                            {
                                foreach($val as $kk=>$vv)
                                {
                                    ?>
                                    <td style="background-color:e78f08; color:white"><strong><?=@$rspData[$kk]!=""?@$rspData[$kk]:$kk?></strong></td>
                                    <?
                                }
								
								?>
								<td style="background-color:e78f08; color:white"><strong>Үйлдэл</strong></td>
								<!-- Товч нэмээд тухайн товч дарах үед тендер цуцлах хүсэлт илгэхдээ guaranteeId авна. -->
								<?
							
								break;
                            }
                            ?>
                        </tr>
                    <?
					$amtTotal=0;
					foreach($xml->Body->getGuaranteeInfoResponse->return->guaranteeInfo as $key=>$val)
					{
						?>
                        <tr>
                        <?
						foreach($val as $kk=>$vv)
						{
							?>
							<td><?=in_array($kk,array("budget","guaranteeAmount"))?number_format(strval($vv),3):$vv?></td>
							<?
						}
						?>
						<?
							if($val->guaranteeStatus=="Цуцалсан")
								{?>
									<td colspan="2" height="20">
    									<button style="height:30px;" id="<?=@$val->guaranteeId?>" onClick="handleButtonClickga(this.id)">
        								<img src="../images/cancel.png" align="absmiddle"> Цуцлах
    									</button>
									</td>
								<?	
								}
							?>
                        </tr>
                        <?
						$amtTotal+=floatval($val->guaranteeAmount);
					}
					?>
                    </table>
                    <p><strong>Илгээсэн баталгааны нийт дүн:</strong> <strong><?=number_format($amtTotal,3)?></strong></p>
                    </td>
                  </tr>
                <?
				#End Банкнаас илгээсэн харилцагчийн баталгаанууд
				?>
				  <tr>
					<td colspan="2" height="20"></td>
				  </tr>
				<?
				$strGuaranteeInfo=ob_get_contents();
				ob_end_clean();

				//Active tender
				if($xml->Body->getGuaranteeInfoResponse->return->guaranteeInfo->guaranteeStatus=="Цуцалсан")
				{
				  if(@$_REQUEST["cmd"]=="amend") 
				  {
					?>
					<table border="0" cellspacing="0" cellpadding="2" style="margin-top:16px;" width="100%">
					  <tr><td width="25%"></td><td></td></tr>
					  <tr>
						<td colspan="2" style="background-color:red; color:white"><strong>II. Тендерийн бүртгэлийн мэдээлэл</strong></td>
					  </tr>
                	<tr>
                    <td colspan="2" style="color:red;" height="40"><strong>Тендерийн баталгааг засах боломжгүй, уг баталгаа цуцлагдсан байна!!!</strong></td>
                  </tr>
                  <?
				  }
				  echo $strGuaranteeInfo;
				  ?>
                </table>
                    <?
					break;
				}
				
				$xmlDom = new DomDocument('1.0','UTF-8');
				$xmlDom->formatOutput = true;
				
				//Header Setting
				$xmlEnvelope = $xmlDom->appendChild($xmlDom->createElement('soap-env:Envelope')); 
				$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
				$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
				$xmlBody = $xmlEnvelope->appendChild($xmlDom->createElement('soap-env:Body')); 
				$xmlAction = $xmlBody->appendChild($xmlDom->createElement("web:".@$gDoc[$vDocID][0])); 
			
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('username')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["username"])); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('password')); 
				$xmlItm->appendChild($xmlDom->createTextNode($gHeader["password"])); 
				
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('invitationNumber')); 
				$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtNumberID']))); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('bidderRegisterNumber')); 
				$xmlItm->appendChild($xmlDom->createTextNode(trim(@$_REQUEST['txtID']))); 
				$xmlItm = $xmlAction->appendChild($xmlDom->createElement('supplierTypeCode')); 
				$xmlItm->appendChild($xmlDom->createTextNode("ORGANIZATION"));
			}



			?>
			<table border="0" cellspacing="0" cellpadding="2" style="margin-top:16px;" width="100%">
              <tr><td width="25%"></td><td></td></tr>
			<?


			$Soap = $xmlDom->saveXML();
			fnWrite2Log("ETENDER", "Request: getTenders", $Soap);
		
			$Service = new Webservice($gUrl, "SOAP", "utf-8");
			flush();
			$Response = $Service->SendRequest(str_replace("\'","'",$Soap), @$Action);

			if($_SESSION["EMPID"]==48806)
			{
				print_r($Soap);
				print_r($Response);
			}
		
			fnWrite2Log("ETENDER", "Response: getTenders", @$Response["Body"]);
			if($Response["Status"]!=200)
			{
				echo "<p style=\"color:#FF0000\"><strong>Error:</strong><br>";
				print_r($Response);
				echo "</p>";
				break;
			}
			$strData=str_replace("S:","",$Response["Body"]);
			$strData=str_replace("ns2:","",$strData);
            $strData=str_replace("''","",$strData);
			try {
				$xml = @new SimpleXMLElement($strData);
			}
			catch(Exception $e){
				print_r($e);
				break;
			}
		//Common Header Part
			$Receive_Time = date("Y/m/d h:i:s");
			$Result_Code = $xml->Body->getTendersResponse->return->responseResultType;

		//Success
			if(!in_array($Result_Code,array("SUCCESS")) || $xml->Body->getTendersResponse->return->failureMessages->message->failureCode!="")
			{
				echo "<p style=\"color:red\"><br>Fail !!!";
				$Result_Code = $xml->Body->getTendersResponse->return->failureMessages->message->failureCode;
				$Result_Message = $xml->Body->getTendersResponse->return->description."<br>";
				foreach($xml->Body->getTendersResponse->return->failureMessages->message as $k=>$v)
					$Result_Message .= $v->failureCode.". ".$v->failureMessage."<br>";
				echo "<br>Алдааны код: ".$Result_Code;
				echo "<br>Алдааны мэдээлэл: ".$Result_Message,"</p>";
				break;
			}
			?>
			  <tr>
				<td colspan="2" nowrap style="background-color:green; color:white;"><strong>III. Харилцагчийн сонгосон тендер болон багцууд</strong></td>
              </tr>
			<?
			foreach($xml->Body->getTendersResponse->return->tenderInfo->children() as $key=>$val)
			{
				if($key=="tenderPackageList") continue;
				?>
              <tr>
				<td><strong><?=@$rspData[$key]!=""?@$rspData[$key]:$key?></strong>:</td>
				<td><?=in_array($key,array("budget","guaranteeAmount"))?number_format(strval($val),3):$val?></td>
              </tr>
                <?
			}
			
			$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList/tenderName");

			if(count((array)$tenders)>0)
			{
			?>
                <tr><td colspan="2">
                  <div id="divTenders" style="width:100%">
                    <ul>
                    <?
					foreach($tenders as $k=>$v)
					{
						?>
						<li><a href="#divTender<?=$k?>"><?=$v?></a></li>
                        <?
					}
				?>
                    </ul>
                <?
					$amtTotal=0;
					$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");

					foreach($tenders as $k=>$v)
					{
				?>
                    <div id="divTender<?=$k?>" style="width:100%; padding:0; margin:0;">
                <?
						foreach($v->children() as $kk=>$vv)
						{
				?>
                        <div style="width:25%; white-space:nowrap; float:left"><strong><?=@$rspData[$kk]!=""?@$rspData[$kk]:$kk?></strong>:</div>
                        <div style="width:75%; white-space:nowrap; float:left"><?=in_array($kk,array("budget","guaranteeAmount"))?number_format(strval($vv),3):$vv?></div>
                <?
						}
				?>
                    </div>
				<?
						$amtTotal+=floatval($v->guaranteeAmount);
					}
				?>
                  </div>
				</td></tr>
                <tr><td colspan="2" height="30"><strong>Сонгосон баталгааны нийт дүн:</strong> <strong><?=number_format($amtTotal,3)?></strong></td></tr>
              <? }?>
            </table>	
			<script>
				<? if(count((array)$tenders)>0) {?>$( "#divTenders" ).tabs();<? }?>
				$('#txtTenderNameGa').val("<?=trim(str_replace('"', "" , str_replace("'", "" ,str_replace('\\', '/', $xml->Body->getTendersResponse->return->tenderInfo->tenderName))))?>");
				<?
					$tenderID=@$xml->Body->getTendersResponse->return->tenderInfo->tenderPackageList->tenderId;
					if($tenderID=="") $tenderID=$xml->Body->getTendersResponse->return->tenderInfo->tenderId;
					
					$tenCode=@$xml->Body->getTendersResponse->return->tenderInfo->tenderPackageList->tenderCode;
					if($tenCode=="") $tenCode=$xml->Body->getTendersResponse->return->tenderInfo->tenderCode;
					
					$tenders=$xml->xpath("/Envelope/Body/getTendersResponse/return/tenderInfo/tenderPackageList");
					if(count((array)$tenders)>1) #1-с их багцийн тендертэй бол
					{
						$strTenders="";
						foreach($tenders as $kkk=>$vvv)
						{
							$strTenders.="<option value=\"".$vvv->tenderId.":".$vvv->tenderCode."\">".$vvv->tenderId." - ".$vvv->tenderCode.", ".$vvv->tenderName."</option>";
						}
						if(@$_REQUEST["cmd"]=="amend"/*|| !in_array($_SESSION["EMPID"],array(415,1045))*/)
						{
						?>
							$('#tdTenderIDGa').html('<select name="txtTenderIDGa" class="guarGa" id="txtTenderIDGa" style="width: 100%" title="Тендерийн урилгын дугаарыг оруулна уу!"><option value=\"\"><?=@$_REQUEST["cmd"]=="amend"?"Засах":"Илгээх"?> багцаа сонгоно уу</option><?=$strTenders?></select>');
						<? 
						}
						else
						{
						?>
							$('#tdTenderIDGa').html('<select name="txtTenderIDGa[]" multiple="multiple" class="guarGa" id="selTenderIDGa" style="width: 100%" title="Тендерийн урилгын дугаар буюу илгээх багцийг сонгоно уу!"><?=$strTenders?></select>');
							$("#selTenderIDGa").multipleSelect({filter: true, placeholder: '<?=@$_REQUEST["cmd"]=="amend"?"Засах":"Илгээх"?> багцаа сонгоно уу',selectAllText: 'Бүх багцийг сонгох'});
						<? 
						}
					}
					else { #1 багцтай болон багцгүй тендер бол
					?>
					$('#tdTenderIDGa').html('<input name="txtTenderIDGa" type="text" class="guarGa" id="selTenderIDGa" title="Тендерийн урилгын дугаарыг оруулна уу!" style="width: 100%" maxlength="40" readonly value="<?=$tenderID.":".$tenCode?>">');
				<?  }?>
				
				$('#txtOrgName1Ga').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->bidderName?>');
				$('#txtOrgID1Ga').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->bidderRegisterNumber?>');
				$('#txtOrgNameGa').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->clientName?>');
				$('#txtOrgIDGa').val('<?=$xml->Body->getTendersResponse->return->tenderInfo->clientRegisterNumber?>');

				$('#divTenderDataGa').html($('#divTenderDataGa').html()+'<input type="hidden" name="tenInvitationNumberga" id="tenInvitationNumberga" value="<?=trim(@$_REQUEST['txtNumberID'])?>">');
				$('#divTenderDataGa').html($('#divTenderDataGa').html()+'<input type="hidden" name="tenSupplierTypeCodega" id="tenSupplierTypeCodega" value="<?=@$gDic["supplierTypeCode"][@$row['TYPECODE']]?>">');
				$('#divTenderDataGa').html($('#divTenderDataGa').html()+'<input type="hidden" name="tenOrgTenderDataga" id="tenOrgTenderDataga" value="<?=base64_encode(@gzcompress($strData,9))?>">');
				<? if(@$gGuaranteeID!="") {?>$('#divTenderDataGa').html($('#divTenderDataGa').html()+'<input type="hidden" name="tenGuaranteeIDga" id="tenGuaranteeIDga" value="<?=@$gGuaranteeID?>">');<? }?>

            </script>
	 	<?
		break;
		case "11" : 
			
			$xmlDomForCancel = new DomDocument('1.0','UTF-8');
			$xmlDomForCancel->formatOutput = true;
			
			//Header Setting
			$xmlEnvelope = $xmlDomForCancel->appendChild($xmlDomForCancel->createElement('soap-env:Envelope')); 
			$xmlEnvelope->setAttribute('xmlns:soap-env',"http://schemas.xmlsoap.org/soap/envelope/"); 
			$xmlEnvelope->setAttribute('xmlns:web',"http://webservice.bank.guarantee.meps.project.interactive.mn/"); 
			$xmlBodyForCancelation = $xmlEnvelope->appendChild($xmlDomForCancel->createElement('soap-env:Body')); 
			$xmlActionForCancel = $xmlBodyForCancelation->appendChild($xmlDomForCancel->createElement("web:".@$gDoc[9][0])); 
		
			$xmlItmForCancel = $xmlActionForCancel->appendChild($xmlDomForCancel->createElement('username')); 
			$xmlItmForCancel->appendChild($xmlDomForCancel->createTextNode($gHeader["username"])); 
			$xmlItmForCancel = $xmlActionForCancel->appendChild($xmlDomForCancel->createElement('password')); 
			$xmlItmForCancel->appendChild($xmlDomForCancel->createTextNode($gHeader["password"])); 
			

			$xmlItmForCancel = $xmlActionForCancel->appendChild($xmlDomForCancel->createElement('guaranteeId')); 
			$xmlItmForCancel->appendChild($xmlDomForCancel->createTextNode(@$_REQUEST['id']));
			
			
			$SoapForCancel = $xmlDomForCancel->saveXML();
			fnWrite2Log("ETENDER", "Request: guaranteeReturn", $SoapForCancel);

			$Service = new Webservice($gUrl, "SOAP", "utf-8");
			flush();
			$ResponseForCancel = $Service->SendRequest(str_replace("\'","'",$SoapForCancel), "guaranteeReturn");
			fnWrite2Log("ETENDER", "ResponseForCancel: guaranteeReturn", @$ResponseForCancel["Body"]);
			if($ResponseForCancel["Status"]!=200)
			{
				echo json_encode(array("error"=>"1","msg"=>"Баталгааг цуцалж чадсангүй холболтын алдаа гарлаа!\nТа дахин баталгааг цуцлана уу."));
				// break;
			}
			// echo json_encode(array("error"=>"400","msg"=>$ResponseForCancel["Body"]));
			fnWrite2Log("ETENDER", "guaranteeReturn: Empid", @$_SESSION['EMPID']);
			$strSQL="UPDATE etendergadaad SET typecode='02',receivetime1=sysdate,resultmessage1='Цуцлагдсан',cnclempid = ".@$_SESSION['EMPID'].", resultcode1='SUCCESS',transmissionid1='".$SndTransmission_Number."'
					WHERE tenderno='".@$_REQUEST['id']."' 
						AND status=1";
			$result=@ociparse($connectiongb,$strSQL);
			if(!@ociexecute($result,OCI_COMMIT_ON_SUCCESS))
			{
				$strMsg="Баталгааны бичилтийг хийж чадсангүй алдаа гарлаа\n";
				$strMsg.="Цуцалж байгаа тендерийн төлвийг шалгана уу!\n";
				$strMsg.="------------------------------------------------------\n";
				$strMsg.="Илгээсэн дугаар: ".$SndTransmission_Number."\n";
				echo json_encode(array("error"=>"1","msg"=>$strMsg));
				break;
			}
			$strMsg="Баталгаа амжилттай цуцлагдлаа";
			echo json_encode(array("error"=>"0","msg"=>$strMsg));
			break;
	default:
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Цахим тендерийн баталгаа</title>
<LINK href="../../style.css" type=text/css rel=stylesheet>
<link href="../../modules/jquery/jquery_ui/css/ui-lightness/jquery-ui-1.8.6.custom.css" rel=stylesheet>
<script src="../../modules/jquery/jquery_ui/js/jquery-1.8.2.js"></script>
<script src="../../modules/jquery/jquery_ui/js/jquery-ui-1.9.0.custom.js"></script>
<script src="../../modules/jquery/jquery_ui/js/multiple-select.js"></script>
<link href="../../modules/jquery/jquery_ui/js/multiple-select.css" rel="stylesheet">
</head>
<body>
    <p>
        <div style="float:left"><strong>Цахим тендерийн баталгаа <? $strMsg=array("amend"=>"засах", "cancel"=>"цуцлах"); echo @$strMsg[@$_REQUEST["cmd"]]?></strong></div>
        <div style="float:right"><strong>Салбар:</strong> <?=$_SESSION['BRANCHNO']?></div>
        <div style="clear:both"></div>
    </p>
    <p><a href="eTender.pdf" target="_blank"><img src="../../quiz/learn/images/ext/pdf.gif" align="absmiddle" style="padding-right:8px">Цахим тендер шалгаруулалт зохион байгуулах журам</a><br>
       <a href="eTender_OrgGuide.pdf" target="_blank"><img src="../../quiz/learn/images/ext/pdf.gif" align="absmiddle" style="padding-right:8px">Цахим тендерийн систем ашиглах харилцагчийн гарын авлага</a></p>
        <a href="etender_batalgaa.pdf" target="_blank"><img src="../../quiz/learn/images/ext/pdf.gif" align="absmiddle" style="padding-right:8px">ТӨБЗГ-н зөвлөмж 2017.06.13</a></p>
  <div id="divTab" style="width:100%">
    <ul>
        <li><a href="#divInfo">1. Тендерт оролцогчийн мэдээлэл</a></li>
        <? if(@$_REQUEST["cmd"]!="cancel") {?>
        <li><a href="#divGuar">2. Тендерийн баталгаа</a></li>
        <? }?>
		<? if(@$_REQUEST["cmd"]!="cancel" && $_SESSION['BRANCHNO'] == 494 || $_SESSION["EMPID"]==48806) {?>
		<li><a href="#gadaadDivGuar"> 3. Гадаад тендерийн баталгаа </a></li>
		<? }?>
        <li><a href="#divFind">Тендерийн хайлт</a></li>
    </ul>
    <div id="divInfo" style="width:100%; padding:0; margin:0">
    	<form action="" name="frmInfo" id="frmInfo" enctype="multipart/form-data" onSubmit="return false;">
        <table style="width:100%;">
            <tr> 
                <td width="5%" nowrap>Харилцагчийн регистрийн дугаар:</td>
                <td><input name="txtID" type="text" title="Харилцагчийн регистрийн дугаарыг оруулна уу!" id="txtID" size="20" maxlength="20" class="info" value=""></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын дугаар:</td>
                <td><input name="txtNumberID" type="text" title="Тендерийн урилгын дугаарыг оруулна уу!" id="txtNumberID" value="" size="20" maxlength="40" class="info"></td>
            </tr>
            <? if($_REQUEST["cmd"]!="") {?>

				<?if($_SESSION['BRANCHNO'] == 494 || $_SESSION["EMPID"]==48806){?>
				<tr>
					 <td> Гадаад тендерийн баталгаа цуцлах : </td>
					 <td> <input type="checkbox" id="vehicle1" name="vehicle1" value="" onChange="cnTenderG()" ></td>
			</tr>
			<? } ?>
			<tr id="gadaadseltype" style="display: none;" >
              	<td>Баталгааны төрөл:</td>
              	<td><select name="selTypega" id="selTypega">
                <?
					foreach($gDicGa["guaranteeTypeCode"] as $k=>$v)
						echo "<option value=\"".$k."\" title=\"".$v."\">".$v."</option>";
				?></select></td>
            </tr>



            <tr id= "tenderSelType">
              <td>Баталгааны төрөл:</td>
              <td><select name="selType" id="selType">
                <?
					foreach($gDic["guaranteeTypeCode"] as $k=>$v)
						echo "<option value=\"".$k."\" title=\"".$v."\">".$v."</option>";
				?></select></td>
            </tr>
            <tr id="sendTypetr">
                <td>Илгээх төрөл:</td>
                <td><select name="sendType" id="sendType">
                        <option value="1">ХХБ</option>
                        <option value="2">УБХБ/хуучин/</option>
                       </select>
                </td>
            </tr>
            <? }else {
			if(@$_SESSION['EMPID'] == 48806|| @$_SESSION['EMPID']== 26892 || $_SESSION['BRANCHNO'] == 494 ){?>
			<tr>
				<td width="5%" nowrap>Grape-ээс лавлахгүй:</td>
				<td> <input type="checkbox" id="vehicle1" name="vehicle1" value="" onChange="" ></td>
			</tr>

			<? }} ?>

			<tr> 
                <td></td>
                <td><button name="btnFind" id="btnFind" onClick="
		                if(fnChkData('info'))
                        {
							if(!checkcase()){
								$('#divTenderData').html('');
                				$('#rspInfo').html('<img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...');
                				$('#rspInfo').load('index.php?xml=1<?=@$_REQUEST["cmd"]!=""?"&cmd=".@$_REQUEST["cmd"]:""?>',$('#frmInfo').serializeArray());
							}else {
								$('#divTenderDataGa').html('');
                        		$('#rspInfo').html('<img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...');
                            	$('#rspInfo').load('index.php?xml=3',$('#frmInfo').serializeArray());
							}
                        }
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Хайх</button></td>
            </tr>
        </table>
        </form>
        <div id="rspInfo"></div>
    </div>
	<? if(@$_REQUEST["cmd"]!="cancel") {?>
    <div id="divGuar" style="width:100%; padding:0; margin:0">
    	<form action="" name="frmGuar" id="frmGuar" enctype="multipart/form-data" onSubmit="return false;">
        <table style="width:100%;">
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын нэр: <label style="color:red">*</label></td>
                <td><input name="txtTenderName" type="text" class="guar" id="txtTenderName" style="width:100%" title="Тендерийн урилгын нэрийг оруулна уу!" maxlength="250" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын дугаар: <label style="color:red">*</label></td>
                <td id="tdTenderID"><input name="txtTenderID" type="text" class="guar" id="selTenderID" title="Тендерийн урилгын дугаарыг оруулна уу!" size="15" maxlength="40" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Захиалагч байгууллагын нэр: <label style="color:red">*</label></td>
                <td><input name="txtOrgName" type="text" class="guar" id="txtOrgName" style="width:100%" title="Захиалагч байгууллагын нэрийг оруулна уу!" value="" maxlength="200" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Захиалагч байгууллагын регистрийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtOrgID" type="text" class="guar" id="txtOrgID" title="Захиалагч байгууллагын регистрийн дугаарыг оруулна уу!" value="" size="15" maxlength="12" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н харилцагчийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtOrgRetailCustNo" type="text" id="txtOrgRetailCustNo" value="" size="12" maxlength="40" readonly> <input name="txtOrgCustNo" type="text" id="txtOrgCustNo" value="" size="6" maxlength="40" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н нэр: <label style="color:red">*</label></td>
                <td><input name="txtOrgName1" type="text" class="guar" id="txtOrgName1" style="width:100%" title="ААН-н нэрийг оруулна уу!" value="" maxlength="200" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н регистрийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtOrgID1" type="text" class="guar" id="txtOrgID1" title="ААН-н регистрийн дугаарыг оруулна уу!" size="15" maxlength="12" readonly></td>
            </tr>
            <tr>
              <td nowrap>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td nowrap>Баталгааны дугаар: <label style="color:red">*</label></td>
              <td valign="middle">
              	<input name="txtRefNo" type="text" title="Баталгааны дугаарыг оруулна уу!" id="txtRefNo" maxlength="40" class="graft" value="">:
                <input name="txtAmendNo" type="text" class="graft" id="txtAmendNo" title="Баталгааны өөрчлөлтийн дугаарыг оруулна уу!" size="3" maxlength="5" value="00">
				<button name="btnFind" id="btnFind" onClick="
		                if(fnChkData('graft'))
                        {
                			$('<div id=\'rspGraft\'><img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...</div>').load('index.php?xml=998&id='+$('#txtRefNo').val()+'&amend='+$('#txtAmendNo').val()).dialog({
                            	title: 'Баталгааны мэдээлэл',
                                position: 'top',
                                modal: true,
                                width: 600,
                                dialogClass: 'rspGraft',
                                beforeClose: function(){ $('#rspGraft').remove(); },
                                buttons: {
                                	'Үргэлжлүүлэх': function(){
                                    	if(!fnChkData('graft')) return;

										if($('#EndDate').val() != 'Тендер хүчинтэй байх хугацаанаас хойш 28 хоногийн хугацаанд'){
											alert('Баталгааны дуусах огноо тендер нээснээс хойш 28 хоногийн хугацаанд байх ёстой !');
											return;
										}
                                        $('#txtBrch').val($('#Brch').val());
                                        $('#txtBrchDir').val($('#BrchDir').val());
                                        $('#txtEmp').val($('#Emp').val());
                                        $('#txtComment').val($('#Comment').val());
                                        $('#txtAmt').val($('#Amt').val());
                                        $('#selCurCode').val($('#CurCode').val());
                                        $('#txtStartDate').val($('#StartDate').val());
                                        $('#txtEndDate').val($('#EndDate').val());
                                        $('#txtOrgRetailCustNo').val($('#TenderRetailCustNo').val());
                                        $('#txtOrgCustNo').val($('#TenderCustNo').val());
                                    	$(this).dialog('close');
                                    	$('#rspGraft').remove();
                                    },
                                	'Буцах': function(){
                                    	$(this).dialog('close');
                                    }
                                }
                            });
                        }
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Хайх</button>                
              </td>
            </tr>
            <tr>
              <td nowrap>&nbsp;</td>
              <td><div id="divTenderData" style="display:none"></div><div id="divOrgData" style="display:none"></div></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагчийн нэр: <label style="color:red">*</label></td>
                <td><input name="txtBankName" type="text" class="guar" id="txtBankName" style="width:100%" title="Баталгаа гаргагчийн нэрийг оруулна уу!" value="<?=$gHeader["SenderName"]?>" maxlength="200" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагчийн регистрийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtBankID" type="text" class="guar" id="txtBankID" title="Баталгаа гаргагчийн регистрийн дугаарыг оруулна уу!" value="<?=$gHeader["SenderID"]?>" size="15" maxlength="12" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан салбарын нэр: <label style="color:red">*</label></td>
                <td><input name="txtBrch" type="text" class="guar" id="txtBrch" style="width:100%" title="Баталгаа гаргасан салбарын нэрийг оруулна уу!" maxlength="250" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан салбарын захирлын нэр: <label style="color:red">*</label></td>
                <td><input name="txtBrchDir" type="text" class="guar" id="txtBrchDir" style="width:100%" title="Баталгаа гаргасан салбарын захирлын нэрийг оруулна уу!" maxlength="250" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагчийн хаяг: <label style="color:red">*</label></td>
                <td><input name="txtBrchAddr" type="text" class="guar" id="txtBrchAddr" style="width:100%" title="Баталгаа гаргагчийн хаягийг оруулна уу!" maxlength="200" readonly value="<?=@$gCommon["PostalRelationAddress"]?>"></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааг хариуцсан ажилтан: <label style="color:red">*</label></td>
                <td><input name="txtEmp" type="text" class="guar" id="txtEmp" title="Баталгааг хариуцсан ажилтныг оруулна уу!" maxlength="50" readonly></td>
            </tr>
            <tr> 
                <td width="5%" valign="top" nowrap>Баталгааны агуулга:</td>
                <td><textarea name="txtComment" cols="45" rows="5" style="width:100%" id="txtComment" maxlength="2000"></textarea></td>
            </tr>
            <tr> 
              <td width="5%" nowrap>Баталгааг хариуцсан ажилтны утасны дугаар:</td>
                <td><input name="txtEmpPhone" type="text" id="txtEmpPhone" value="<?=@$gCommon["Phone"]?>" maxlength="40" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааг хариуцсан ажилтны факс:</td>
                <td><input name="txtEmpFax" type="text" id="txtEmpFax" value="<?=@$gCommon["Fax"]?>" maxlength="40" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааг хариуцсан ажилтны и-мэйл:</td>
                <td><input name="txtEmpMail" type="text" id="txtEmpMail" value="<?=@$gCommon["eMail"]?>" maxlength="50" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааны хэлбэр: <label style="color:red">*</label></td>
                <td><select name="selTypeCode" class="guar">
                <?
					foreach($gDic["guaranteeTypeCode"] as $k=>$v)
						echo "<option value=\"".$k."\" title=\"".$v."\">".$v."</option>";
				?></select>
                </td>
            </tr>
            <tr> 
              <td width="5%" nowrap>Баталгааны дүн/валют: <label style="color:red">*</label></td>
                <td><input name="txtAmt" type="text" class="guar" id="txtAmt" title="Баталгааны дүн/валютыг оруулна уу!" value="" readonly> <select name="selCurCode" class="guar" id="selCurCode">
                <?
					$strSQL="SELECT curcode,curname FROM gb.pacur c ORDER BY listorder";
					$result=@ociparse($connectiongbsb,$strSQL);
					@ociexecute($result,OCI_DEFAULT);
                	while(ocifetchinto($result,$row,OCI_BOTH))
						echo "<option value=\"".$row["CURCODE"]."\" title=\"".$row["CURNAME"]."\">".$row["CURCODE"]."</option>";
				?></select>
                </td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан огноо: <label style="color:red">*</label></td>
                <td><input name="txtStartDate" type="text" class="guar" id="txtStartDate" title="Баталгаа гаргасан огноог оруулна уу!" value="" size="12" maxlength="10" readonly> yyyy.mm.dd</td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааны хүчинтэй хугацаа: <label style="color:red">*</label></td>
                <td><input name="txtEndDate" type="text" class="guar" id="txtEndDate" title="Баталгааны хүчинтэй хугацааг оруулна уу!" value="" size="100" maxlength="100" readonly></td>
            </tr>
            <tr> 
                <td></td>
                <td><button name="btnGuar" id="btnGuar" onClick="
                        if(confirm('Та тендерийн баталгааг Сангийн Яам руу илгээхдээ итгэлтэй байна уу!') && fnChkData('guar'))
                        {
							$('<div id=\'rspGuar\'><img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Баталгааг илгээж байна...</div>').dialog({
                            	title: 'Баталгааг илгээж байна...',
                                position: 'top',
                                height: 200,
                                width: 300,
                                modal: true
                            });
                            $.ajax({
                            	url: 'index.php?xml=0',
                                type: 'POST',
                                data: $('#frmGuar').serializeArray(),
                                success: function(msg){
                                	var json = jQuery.parseJSON(msg);
                                    $('#rspGuar').remove();
									alert(json.msg);
									if (json.error == 0) 
                                    	window.location='index.php<?=@$_REQUEST["cmd"]!=""?"?cmd=".@$_REQUEST["cmd"]:""?>';
                                }
                            });
                        }
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Баталгаа илгээх</button></td>
            </tr>
        </table>
  </form>
    </div>
    <? }?>


	<!--Эндээс хуулсан мэдээлэл эхлэж байна-->
<? if(@$_REQUEST["cmd"]!="cancel" && $_SESSION['BRANCHNO'] == 494 || $_SESSION["EMPID"]==48806) {?>
    <div id="gadaadDivGuar" style="width:100%; padding:0; margin:0">
    	<form action="" name="frmGuarGa" id="frmGuarGa" enctype="multipart/form-data" onSubmit="return false;">
        <table style="width:100%;">
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын нэр: <label style="color:red">*</label></td>
                <td><input name="txtTenderNameGa" type="text" class="guarGa" id="txtTenderNameGa" style="width:100%" title="Тендерийн урилгын нэрийг оруулна уу!" maxlength="250" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Тендерийн код: <label style="color:red">*</label></td>
                <td id="tdTenderIDGa"><input name="txtTenderIDGa" type="text" class="guarGa" id="selTenderIDGa" title="Тендерийн урилгын дугаарыг оруулна уу!" size="15" maxlength="40" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Захиалагч байгууллагын нэр: <label style="color:red">*</label></td>
                <td><input name="txtOrgNameGa" type="text" class="guarGa" id="txtOrgNameGa" style="width:100%" title="Захиалагч байгууллагын нэрийг оруулна уу!" value="" maxlength="200" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Захиалагч байгууллагын регистрийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtOrgIDGa" type="text" class="guarGa" id="txtOrgIDGa" title="Захиалагч байгууллагын регистрийн дугаарыг оруулна уу!" value="" size="15" maxlength="12" readonly></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н харилцагчийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtOrgRetailCustNoGa" type="text" id="txtOrgRetailCustNoGa" value="" size="12" maxlength="40" ></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н нэр: <label style="color:red">*</label></td>
                <td><input name="txtOrgName1Ga" type="text" class="guarGa" id="txtOrgName1Ga" style="width:100%" title="ААН-н нэрийг оруулна уу!" value="" maxlength="200" ></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>ААН-н регистрийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtOrgID1Ga" type="text" class="guarGa" id="txtOrgID1Ga" title="ААН-н регистрийн дугаарыг оруулна уу!" size="15" maxlength="12" ></td>
            </tr>
			<tr>
              <td nowrap>Баталгааны дугаар: <label style="color:red">*</label></td>
              <td valign="middle">
              	<input name="batalgaanumberga" type="text" title="Баталгааны дугаарыг оруулна уу!" id="batalgaanumberga" maxlength="40" value="">:
                <input name="txtAmendNoga" type="text" id="txtAmendNoga" title="Баталгааны өөрчлөлтийн дугаарыг оруулна уу!" size="3" maxlength="5" value="00">          
              </td>
            </tr>
			<tr> 
                <td width="5%" nowrap>Тендерийн урилгын дугаар: <label style="color:red">*</label></td>
                <td id="tdTenderIDInvGa"><input name="txtTenderInvGa" type="text" class="guarGa" id="txtTenderInvGa" title="Тендерийн урилгын дугаарыг оруулна уу!" size="15" maxlength="40"></td>
            </tr>
            <tr>
              <td nowrap>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td nowrap>&nbsp;</td>
              <td><div id="divTenderDataGa" style="display:none"></div><div id="divOrgData" style="display:none"></div></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагч банкны нэр: <label style="color:red">*</label></td>
                <td><input name="txtBankNameGa" type="text" class="guarGa" id="txtBankNameGa" style="width:100%" title="Баталгаа гаргагчийн нэрийг оруулна уу!" value="" maxlength="200" ></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагч банкны регистрийн дугаар: <label style="color:red">*</label></td>
                <td><input name="txtBankIDGa" type="text" class="guarGa" id="txtBankIDGa" title="Баталгаа гаргагчийн регистрийн дугаарыг оруулна уу!" value="" size="15" maxlength="12" ></td>
            </tr>
			<tr> 
                <td width="5%" nowrap>Баталгаа гаргагч банкны код: <label style="color:red">*</label></td>
                <td><select name="txtBankIDcodeGa" class="guarGa">
                <?
					foreach($gDicGa["BankCode"] as $k=>$v)
						echo "<option value=\"".$k."\" title=\"".$v."\">".$v."</option>";
				?></select>
                </td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагч банкны удирдлагын нэр: <label style="color:red">*</label></td>
                <td><input name="txtBrchDirGa" type="text" class="guarGa" id="txtBrchDirGa" style="width:100%" title="Баталгаа гаргасан салбарын захирлын нэрийг оруулна уу!" maxlength="250" ></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргагч банкны ажилтны нэр: <label style="color:red">*</label></td>
                <td><input name="txtEmpGa" type="text" class="guarGa" id="txtEmpGa" title="Баталгааг хариуцсан ажилтныг оруулна уу!" maxlength="50" ></td>
            </tr>
            <tr> 
                <td width="5%" valign="top" nowrap>Баталгааны агуулга:</td>
                <td><textarea name="txtCommentGa" cols="45" rows="5" style="width:100%" id="txtCommentGa" maxlength="2000"></textarea></td>
            </tr>
            <tr> 
              <td width="5%" nowrap>Баталгаа гаргаж буй банкны ажилтны утасны дугаар:</td>
                <td><input name="txtEmpPhoneGa" type="text" id="txtEmpPhoneGa" value="" maxlength="40" ></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргаж буй банкны ажилтны и-мэйл хаяг:</td>
                <td><input name="txtEmpMailGa" type="text" id="txtEmpMailGa" value="" maxlength="50" ></td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгааны хэлбэр: <label style="color:red">*</label></td>
                <td><select name="selTypeCodeGa" class="guarGa">
                <?
					foreach($gDicGa["guaranteeTypeCode"] as $k=>$v)
						echo "<option value=\"".$k."\" title=\"".$v."\">".$v."</option>";
				?></select>
                </td>
            </tr>
            <tr> 
              <td width="5%" nowrap>Баталгааны дүн/валют: <label style="color:red">*</label></td>
                <td><input name="txtAmtGa" type="text" class="guarGa" id="txtAmtGa" title="Баталгааны дүн/валютыг оруулна уу!" value="" > <select name="selCurCodeGa" class="guarGa" id="selCurCodeGa">
                <?
					$strSQL="SELECT curcode,curname FROM gb.pacur c ORDER BY listorder";
					$result=@ociparse($connectiongbsb,$strSQL);
					@ociexecute($result,OCI_DEFAULT);
                	while(ocifetchinto($result,$row,OCI_BOTH))
						echo "<option value=\"".$row["CURCODE"]."\" title=\"".$row["CURNAME"]."\">".$row["CURCODE"]."</option>";
				?></select>
                </td>
            </tr>
            <tr> 
                <td width="5%" nowrap>Баталгаа гаргасан огноо: <label style="color:red">*</label></td>
                <td><input name="txtStartDateGa" type="text" class="guarGa" id="txtStartDateGa" title="Баталгаа гаргасан огноог оруулна уу!" value="" size="12" maxlength="10" > yyyy.mm.dd</td>
            </tr>
			<tr> 
                <td width="5%" nowrap>Баталгааны хүчинтэй хугацаа: <label style="color:red">*</label></td>
                <td><input name="txtEndDateGa" type="text" class="guarGa" id="txtEndDateGa" title="Баталгааны хүчинтэй хугацааг оруулна уу!" value="Тендер хүчинтэй байх хугацаанаас хойш 28 хоногийн хугацаанд" size="100" maxlength="100" readonly></td>
            </tr>
			
			<tr> 
   		    	<td width="5%" nowrap>Банкны ажилтны нэр: <label style="color:red">*</label></td>
                <td><input name="Obankempnamega" type="text" class="guarGa" id="Obankempnamega" style="width:100%" title="Баталгаа гаргагчийн нэрийг оруулна уу!" value="" maxlength="200" ></td>
            </tr>


            <tr> 
                <td></td>
                <td><button name="btnGuar" id="btnGuar" onClick="
                        if(confirm('Та тендерийн баталгааг Сангийн Яам руу илгээхдээ итгэлтэй байна уу!') && fnChkData('guarGa'))
                        {
							$('<div id=\'rspGuar\'><img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Баталгааг илгээж байна...</div>').dialog({
                            	title: 'Баталгааг илгээж байна...',
                                position: 'top',
                                height: 200,
                                width: 300,
                                modal: true
                            });
                            $.ajax({
                            	url: 'index.php?xml=10',
                                type: 'POST',
                                data: $('#frmGuarGa').serializeArray(),
                                success: function(msg){
                                	var json = jQuery.parseJSON(msg);
                                    $('#rspGuar').remove();
									alert(json.msg);
									if (json.error == 0) 
                                    	window.location='index.php<?=@$_REQUEST["cmd"]!=""?"?cmd=".@$_REQUEST["cmd"]:""?>';
                                }
                            });
                        }
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Баталгаа илгээх</button></td>
            </tr>
        </table>
  </form>
    </div>
    <? }?>
<!--Энд дуусаж байна даа-->


    <div id="divFind" style="width:100%; padding:0; margin:0">
    	<form action="" name="frmFind" id="frmFind" enctype="multipart/form-data" onSubmit="return false;">
        <table style="width:100%;">
            <tr> 
                <td width="5%" nowrap>Тендерийн урилгын дугаар:</td>
                <td><input name="txtNumberID" type="text" title="Тендерийн урилгын дугаарыг оруулна уу!" id="txtNumberID1" value="" size="20" maxlength="40" class="find"></td>
            </tr>
            <tr> 
                <td></td>
                <td><button name="btnFind" id="btnFind1" onClick="
		                if(fnChkData('find'))
                        {
                            $('#rspFind').html('<img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...');
                            $('#rspFind').load('index.php?xml=2',$('#frmFind').serializeArray());
                        }
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Хайх</button>
                        <a style="text-decoration:none; border:1px solid #cccccc; padding:8px; padding-top:6px; padding-bottom:9px;" id="aFindTender" href="" target="_blank" onClick="this.href='https://tender.gov.mn/mn/invitation?invitationNumber='+$('#txtNumberID1').val();"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;" border="0">Төрийн худалдан авах ажиллагааны цахим системээс лавлах</a>
                        </td>
            </tr>
        </table>
        </form>
        <?
        	if($_SERVER["REMOTE_ADDR"]=="172.26.152.117" || $_SERVER["REMOTE_ADDR"]=="172.26.152.95" || $_SERVER["REMOTE_ADDR"]=="172.26.152.42" || $_SERVER["REMOTE_ADDR"]=="172.26.152.125")
			{
				?>
				<button name="btnFind" id="btnFindCur" onClick="
                            $('#rspFind').html('<img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...');
                            $('#rspFind').load('index.php?xml=5');
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Валютын мэдээлэл</button>				
				<button name="btnFind" id="btnFindBank" onClick="
                            $('#rspFind').html('<img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...');
                            $('#rspFind').load('index.php?xml=6');
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Банкны мэдээлэл</button>				
				<button name="btnFind" id="btnFindBranch" onClick="
                            $('#rspFind').html('<img src=\'../../embassy/images/wait.gif\'> Ta тvр хvлээнэ vv! Мэдээллийг хайж байна...');
                            $('#rspFind').load('index.php?xml=7');
                        return false;"><img src="../images/db_view.png" align="absmiddle" style="padding-right:4px;">Банкны салбарын мэдээлэл</button>				
				<?
			}
		?>
        <div id="rspFind"></div>
    </div>
  </div>
	<script>
		function cnTenderG(){
			var checkbox = document.getElementById('vehicle1');
			if(checkbox != null && checkbox.checked){
				document.getElementById("tenderSelType").style.display = "none";
				
				document.getElementById("sendTypetr").style.display = "none";
				document.getElementById("gadaadseltype").style.display = "table-row";
			}else {
				document.getElementById("gadaadseltype").style.display = "none";
				document.getElementById("tenderSelType").style.display = "table-row";
				document.getElementById("sendTypetr").style.display = "table-row";
			}
		}
		function checkcase(){
			var checkbox = document.getElementById('vehicle1');
			if(checkbox != null && checkbox.checked){
				return true;
			}else {
				return false;
			}
		}
		$(function() {
			$( "#divTab" ).tabs();
			//$( "#txtStartDate" ).datepicker({ dateFormat: "yy.mm.dd" });
			//$( "#txtEndDate" ).datepicker({ dateFormat: "yy.mm.dd" });
			$("input,textarea,select").each(function(index){
				//if($(this).attr('class')=='guar' && $(this).attr('readonly')!='readonly') $(this).addClass("ui-state-highlight");
				$(this).addClass("ui-state-default");
			});
		});
		function fnChkData(val){
			var retcode=true;
			$("."+val).each(function(index){
				<?
				/*if(in_array($_SESSION["EMPID"],array(415,1045)))*/
				if(!in_array(@$_REQUEST["cmd"],array("amend","cancel"))) {?>
				if(val=='guar' && index==2 && retcode==true)
				{
					try{
						if($('#selTenderID').val().length>0) return true;
					}
					catch(e){
						if($(this).attr('title')=='') alert('Мэдээлэл дутуу байна');
						else alert($(this).attr('title'));
						$('#selTenderID').focus();
						retcode=false;
						console.log('catch',this);
					}

					return true;
				}
				<? }?>
				if($(this).val()=='' && retcode==true)
				{
					if(!checkcase()){
					console.log(this);
					if($(this).attr('title')=='') alert('Мэдээлэл дутуу байна');
					else alert($(this).attr('title'));
					$(this).focus();
					retcode=false;
					return true;
					}
				}
			});
			return retcode;
		}
		// function checkclick(){
		// 	document.getElementById("vehicle1").addEventListener('change', function(){
		// 		var inputField = document.getElementById('txtID');
		// 		if(this.checked){
		// 			inputField.readOnly = falses;
		// 		} else {
		// 			inputField.readOnly = true;
		// 		}
		// 	});
		// }
		// function checkclick() {
    	// 	var checkbox = document.getElementById('vehicle1');
    	// 	// checkbox.checked = true;
		// 	var inputField = document.getElementById('txtID');
			
		// 	if(checkbox.checked){
		// 		inputField.readOnly = true;
		// 		inputField.value = '';
		// 	} else {
		// 		inputField.readOnly = false;
		// 	}
		// }

		function handleButtonClick(buttonId) {
    		if(confirm('Тендерийн баталгааг цуцлахдаа итгэлтэй байна уу!')) {
        	$('<div id="rspGuar"><img src="../../embassy/images/wait.gif"> Ta тvр хvлээнэ vv! Баталгааг цуцлаж байна...</div>').dialog({
            	title: 'Баталгааг цуцлаж байна...',
            	position: 'top',
            	height: 200,
            	width: 300,
           	 	modal: true
        	});

       		$.ajax({
            	url: 'index.php?xml=9&id=' + buttonId,
            	type: 'POST',
				data: ($('#divTenderData').serializeArray()).concat($('#frmInfo').serializeArray()),
            	success: function(msg) {
                	var json = jQuery.parseJSON(msg);
                	$('#rspGuar').remove();
                	alert(json.msg);
                	if (json.error == 0) {
                    	window.location = 'index.php<?=@$_REQUEST["cmd"]!=""?"?cmd=".@$_REQUEST["cmd"]:""?>';
                	}
            	}
        	});
    		}
    	return false;
		}
		function handleButtonClickga(buttonId){
			if(confirm('Тендерийн баталгааг цуцлахдаа итгэлтэй байна уу!')) {
        	$('<div id="rspGuar"><img src="../../embassy/images/wait.gif"> Ta тvр хvлээнэ vv! Баталгааг цуцлаж байна...</div>').dialog({
            	title: 'Баталгааг цуцлаж байна...',
            	position: 'top',
            	height: 200,
            	width: 300,
           	 	modal: true
        	});

       		$.ajax({
            	url: 'index.php?xml=11&id=' + buttonId, //гадаад тендер цуцлах 
            	type: 'POST',
				data: ($('#divTenderDataga').serializeArray()).concat($('#frmInfo').serializeArray()),
            	success: function(msg) {
                	var json = jQuery.parseJSON(msg);
                	$('#rspGuar').remove();
                	alert(json.msg);
                	if (json.error == 0) {
                    	window.location = 'index.php<?=@$_REQUEST["cmd"]!=""?"?cmd=".@$_REQUEST["cmd"]:""?>';
                	}
            	}
        	});
    		}
    	return false;
		}

    </script>
</body>
</html>
<?		
			break;
	}
	@eval($gOraSessClose); 
?>
