<?php
	//pr_DebugSave('shift_ajax','_POST='.print_r($_POST,true));//@@@

	$asc_ret=array();//出力 初期化
	
	$post_act=$_POST['act'];//actコード（clickBtnTotalExcel//////）
	
	if($post_act=='clickBtnTotalExcel'){//「登園予定表集計」ボタン クリック
		$asc_ret=fn_clickBtnTotalExcel();
	}else{
		$asc_ret['flg_ret']=false;
		$asc_ret['msg_err']='想定外のactコード：'+$post_act;
	}
	
	header("Content-Type: application/json; charset=utf-8");

	echo json_encode($asc_ret);
?>
<?php
	function fn_clickBtnTotalExcel(){//「登園予定表集計」ボタン クリック
		$asc_ret=array();//戻り値 初期化
		//
		$msg_err='';
		$html='';
		$debug='';

		//
		//POST値
		$tta_PasteToenYotei=$_POST['txa_paste_excel'];//Excelから貼り付けたデータ

		//セル位置情報(A1が00)
		$cell_x_year_month=1;$cell_y_year_month=1;//年月
		$cell_x_kid_name=2;$cell_y_kid_name=2;//園児名
		$cell_x_kid_age=2;$cell_y_kid_age=3;//園児歳児
		$cell_x_time_from=2;$cell_y_time_from=5;//登園時刻
		
		//15分刻み用
		/*
		$asc_time_zone=array(
			 '07:00'=>0,'07:15'=>0,'07:30'=>0,'07:45'=>0,'08:00'=>0,'08:15'=>0,'08:30'=>0,'08:45'=>0
			,'09:00'=>0,'09:15'=>0,'09:30'=>0,'09:45'=>0,'10:00'=>0,'10:15'=>0,'10:30'=>0,'10:45'=>0
			,'11:00'=>0,'11:15'=>0,'11:30'=>0,'11:45'=>0,'12:00'=>0,'12:15'=>0,'12:30'=>0,'12:45'=>0
			,'13:00'=>0,'13:15'=>0,'13:30'=>0,'13:45'=>0,'14:00'=>0,'14:15'=>0,'14:30'=>0,'14:45'=>0
			,'15:00'=>0,'15:15'=>0,'15:30'=>0,'15:45'=>0,'16:00'=>0,'16:15'=>0,'16:30'=>0,'16:45'=>0
			,'17:00'=>0,'17:15'=>0,'17:30'=>0,'17:45'=>0,'18:00'=>0,'18:15'=>0,'18:30'=>0,'18:45'=>0
			,'19:00'=>0,'19:15'=>0,'19:30'=>0,'19:45'=>0,'20:00'=>0,'20:15'=>0,'20:30'=>0,'20:45'=>0
		);
		*/

		//30分刻み用
		$asc_time_zone=array(
			 '07:00'=>0,'07:30'=>0,'08:00'=>0,'08:30'=>0
			,'09:00'=>0,'09:30'=>0,'10:00'=>0,'10:30'=>0
			,'11:00'=>0,'11:30'=>0,'12:00'=>0,'12:30'=>0
			,'13:00'=>0,'13:30'=>0,'14:00'=>0,'14:30'=>0
			,'15:00'=>0,'15:30'=>0,'16:00'=>0,'16:30'=>0
			,'17:00'=>0,'17:30'=>0,'18:00'=>0,'18:30'=>0
			,'19:00'=>0,'19:30'=>0,'20:00'=>0,'20:30'=>0
		);

		//集計結果 [日にちidx][歳児][時間帯]=該当園児数 初期化
		$asc_day=array();
		$asc_total_kids=array('0'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0);
		$asc_total_time_zone=array();
		for($d=1;$d<=31;$d++){//日にちidxループ(1日～31日 $dが日にちと不一致の場合あり)
			//時間帯集計用
			$asc_total_time_zone[$d][0]=$asc_time_zone;//0歳児用
			$asc_total_time_zone[$d][1]=$asc_time_zone;//1歳児用
			$asc_total_time_zone[$d][2]=$asc_time_zone;//2歳児用
			$asc_total_time_zone[$d][3]=$asc_time_zone;//3歳児用
			$asc_total_time_zone[$d][4]=$asc_time_zone;//4歳児用
			$asc_total_time_zone[$d][5]=$asc_time_zone;//5歳児用
		}

		//集計処理
		$asc_cell=array();//セルデータ
		$year_month='';//対象年月
		$kid_sn=0;//園児sn

		///対象年月 記載行
		$ary_col=explode("\t",$ary_row[$cell_y_year_month]);
		$year_month=$ary_col[$cell_x_year_month];//対象年月
		//セルデータ生成
		$y=0;//行カウンタ
		$ary_row=explode("\n",$tta_PasteToenYotei);//行分割
		foreach($ary_row as $row){
			$x=0;//列カウンタ
			$ary_col=explode("\t",$row);
			foreach($ary_col as $term){
				$asc_cell[$x][$y]=$term;
				$x++;
			}
			$y++;
		}
//$html.=print_r($asc_cell,true);
		//
		$cur_x_kid_name=$cell_x_kid_name;//園児名位置(2刻み セル結合しているので)
		while(true){
			$kids_name=$asc_cell[$cur_x_kid_name][$cell_y_kid_name];
			if($kids_name==''){
				break;
			}else{
				$age=$asc_cell[$cur_x_kid_name][$cell_y_kid_age];//歳児(0歳～5歳)
				$asc_total_kids[$age]++;//園児数カウントアップ
				//日数ループzzz
				for($day=1;$day<=31;$day++){//1日～31日ループ
					$date=$asc_cell[$cell_x_year_month][$cell_y_time_from+$day-1];//日にちセル
					if($date!=''){//日付が空欄で無い
						if(isset($asc_day[$day])==false)$asc_day[$day]=$date;
						$time_from=$asc_cell[$cur_x_kid_name][$cell_y_time_from+$day-1];//登園時刻(+2は「1日」位置を指す)
						$time_to=$asc_cell[$cur_x_kid_name+1][$cell_y_time_from+$day-1];//降園時刻
						//加工
						if(strlen($time_from)==1+1+2)$time_from='0'.$time_from;//時が1桁ならゼロを補う
						if(strlen($time_to)==1+1+2)$time_to='0'.$time_to;//時が1桁ならゼロを補う
						//
						$time_range=$time_from.'-'.$time_to;//「自-至」形式
						list($flg_ok,$asc_total_time_zone)=fc_ConvTimeZone($day,$age,$asc_total_time_zone,$time_range);//「自-至」→集計表累計
						if($flg_ok){
							//NOP:後で出力処理
						}else{
							$msg_err.='<br>'.$kids_name.' '.$date.' '.$time_from.'-'.$time_to;
						}
					}
				}
				//
				$cur_x_kid_name+=2;//2行ごと
			}
		}

		//必要保育士計算
		for($day=1;$day<=31;$day++){
			foreach($asc_time_zone as $time_zone=>$tmp){
				$age0=$asc_total_time_zone[$day][0][$time_zone]/3;
				$age1_2=($asc_total_time_zone[$day][1][$time_zone]+$asc_total_time_zone[$day][2][$time_zone])/6;
				$age3=$asc_total_time_zone[$day][3][$time_zone]/20;
				$age4_5=($asc_total_time_zone[$day][4][$time_zone]+$asc_total_time_zone[$day][5][$time_zone])/30;
				//
				if($age0+$age1_2+$age3+$age4_5==0){//園児がいない
					$asc_total_time_zone[$day]['num'][$time_zone]=0;//必要保育士数
				}else{
					$age0=floor($age0*10)/10;//小数第2位以下切り捨て
					$age1_2=floor($age1_2*10)/10;//小数第2位以下切り捨て
					$age3=floor($age3*10)/10;//小数第2位以下切り捨て
					$age4_5=floor($age4_5*10)/10;//小数第2位以下切り捨て
					//
					$num=round($age0+$age1_2+$age3+$age4_5)+1;
					//if($num<2)$num=2;
					$asc_total_time_zone[$day]['num'][$time_zone]=$num;//必要保育士数
				}
			}
		}

		//出力処理
		if($msg_err!=''){
			$msg_err='▼エラー<br>'.$msg_err;
		}else{
			//$html.='<br>asc_total_time_zone='.print_r($asc_total_time_zone,true);
			$html_btn='';$html_tta='';
			//$html_btn.='④下記ボタン クリック後、「職員（保育士）配置計画表（ボタン対応日）」シート上の<br>「D4」セルで貼り付け操作<br>';
			for($day=1;$day<=31;$day++){
				if(isset($asc_day[$day])){
					$date=$asc_day[$day];
					//textarea出力用
					$tta='';
					$tta.='<table>';

					$rec1='';//タイトル1行目（時部）
					$rec2='';//タイトル2行目（分部）
					$rec3='';//必要保育士
					$i=0;
					foreach($asc_time_zone as $time_zone=>$tmp){
						if(($i % 2)==0){//偶数なら
							$rec1.='<th colspan="2" class="th_row">';
							$rec1.=substr($time_zone,0,2);//時部
							$rec1.='</th>';
						}
						$rec2.='<th class="th_row">';
						$rec2.=substr($time_zone,3,2);//分部
						$rec2.='</th>';
						//
						$rec3.='<td>';
						$rec3.=$asc_total_time_zone[$day]['num'][$time_zone];
						$rec3.='</td>';
						//
						$i++;
					}
					$tta.='<tr>';
					$tta.='<th></th>';
					$tta.=$rec1;
					$tta.='</tr>';
					$tta.='<tr>';
					$tta.='<th></th>';
					$tta.=$rec2;
					$tta.='</tr>';

					for($age=0;$age<=5;$age++){
						$tta.='<tr>';
						//$tta.=$asc_total_kids[$age]."人\t";//園児数 出力
						$tta.='<th class="th_row">';
						$tta.=$age.'歳';
						$tta.='</th>';
						foreach($asc_total_time_zone[$day][$age] as $time_zone=>$cnt_kids){
							//$tta.=$cnt_kids."\t";
							$tta.='<td>';
							$tta.=$cnt_kids;
							$tta.='</td>';
						}
						//if($age<5)$tta.="\n";
						$tta.='</tr>';
					}
					
					//必要保育士
					$tta.='<tr>';
					$tta.='<th class="th_row">';
					$tta.='必要保育士数';
					$tta.='</th>';
					$tta.=$rec3;
					$tta.='</tr>';
					
					$tta.='<table>';
					//$tta=str_replace("\t\n","\n",$tta);//行末のTAB除去
					//
					//$html_btn.='<button class="btn_CopyTtaVal" value="tta_total_ToenYotei_'.$day.'">'.$date.'</button>';
					//if(($day==10)||($day==20))$html_btn.='<br>';
					//
					$html_tta.='<br>▼'.$date;
					$html_tta.='<br>';
					//$html_tta.='<textarea rows="7" cols="80" wrap="off" id="tta_total_ToenYotei_'.$day.'">';
					$html_tta.=$tta;
					//$html_tta.='</textarea>';
				}
			}
			//
			//$html.=$html_btn;
			$html.='<hr>';
			$html.=$html_tta;
		}

		//メッセージ
		if($msg_err==''){
			$asc_ret['result']='OK';
			$asc_ret['div_total_ToenYotei']=$html;//集計結果表示エリア（この中に1日から末日までのdivが作成される）
			$asc_ret['debug']=$debug;
		}else{
			$asc_ret['result']='NG';
			$asc_ret['msg']='<span style="color: red">'.$msg_err.'</span>';
			$asc_ret['debug']=$debug;
		}
		//
		return $asc_ret;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//「自-至」→タイムゾーン変換
	//$a_time_range:「時:分-時:分」形式 ※要15分単位
	function fc_ConvTimeZone($a_day,$a_age,$a_asc_total_time_zone,$a_time_range){
		$flg_ok=false;
		$asc_total_time_zone=$a_asc_total_time_zone;
		//
		if($a_time_range=='-'){//登園も降園も空欄時
			$flg_ok=true;//エラーで無い
		}else{
			$ary_time=explode('-',$a_time_range);
			if(Count($ary_time)==2){
				$time_from=$ary_time[0];$time_to=$ary_time[1];
				//時,分が数字2桁判定用
				$time_range=str_replace(':','-',$a_time_range);//デリミタ統一
				$ary_time=explode('-',$time_range);//統一デリミタで分割
				if(Count($ary_time)==4){
					$from_hh=$ary_time[0];$from_mm=$ary_time[1];$to_hh=$ary_time[2];$to_mm=$ary_time[3];
					if((strlen($from_hh)==2)&&(strlen($from_mm)==2)&&(strlen($to_hh)==2)&&(strlen($to_mm)==2)){//時,分 2桁判定
						$from_hh=intval($from_hh);$from_mm=intval($from_mm);$to_hh=intval($to_hh);$to_mm=intval($to_mm);//整数化
						if(($time_from>='07:00')&&($time_from<='20:00')&&($time_to>='07:00')&&($time_to<='20:00')&&($time_from<$time_to)){
							if(($from_mm % 15==0)&&($to_mm % 15==0)){//15分刻み 判定
								$flg_ok=true;//エラーで無い
								//$asc_time_zone 判定ループ
								$flg_belong=false;
								foreach($asc_total_time_zone[$a_day][$a_age] as $time_zone=>$cnt_kids){//時間帯 所属判定ループ zzz:5/17
									if($flg_belong==false){//所属外時
										if($time_zone==$time_from){//所属開始判定
											$flg_belong=true;//所属開始
										}
									}else{//所属中時
										if($time_zone==$time_to){//所属終了判定
											break;//ループ終了
										}
									}
									//
									if($flg_belong){//所属中なら
										$asc_total_time_zone[$a_day][$a_age][$time_zone]=$cnt_kids+1;//カウントアップ
									}
								}
							}
						}
					}
				}
			}
		}
		//
		return array($flg_ok,$asc_total_time_zone);
	}

	//////////////////////////////////////////////////////////////////////////////////
	//
	//$a_tale:保存ファイル名の末尾付加文字（連番等）
	//$a_text:保存内容
	function pr_DebugSave($a_tale,$a_text){
		$dir='_debug/';//出力先フォルダ
		if(file_exists($dir)==false){
			mkdir($dir,0777,true);//true:再帰作成する
			chmod($dir,0777);
		}
		//
		file_put_contents($dir.date('His').'='.$a_tale.'.txt',$a_text);
	}
	
	/*
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function (){
		$ret=;//戻り値初期化
		//
		//
		return $ret;
	}
	*/
?>
