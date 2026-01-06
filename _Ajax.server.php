<?php

require("_Ajax.comun.php"); // No modificar esta linea
include_once './mayorizacion.inc.php';
/* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
  // S E R V I D O R   A J A X //
  :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */


/* * **************************************************************** */
/* DF01 :: G E N E R A    F O R M U L A R I O    P E D I D O       */
/* * **************************************************************** */

function genera_formulario_pedido($tmp = 0, $sAccion = 'nuevo', $aForm = '')
{
    //Definiciones
    global $DSN_Ifx, $DSN;

    if (session_status() !== PHP_SESSION_ACTIVE) {session_start();}

    $oIfx = new Dbo;
    $oIfx->DSN = $DSN_Ifx;
    $oIfx->Conectar();

    $oIfxA = new Dbo;
    $oIfxA->DSN = $DSN_Ifx;
    $oIfxA->Conectar();

    $fu = new Formulario;
    $fu->DSN = $DSN;

    $ifu = new Formulario;
    $ifu->DSN = $DSN_Ifx;

    $oReturn = new xajaxResponse();

    $idempresa          = $_SESSION['U_EMPRESA'];
    $idsucursal         = $_SESSION['U_SUCURSAL'];
    $idbodega_s         = $_SESSION['U_BODEGA'];
    $usuario_informix   = $_SESSION['U_USER_INFORMIX'];
    unset($_SESSION['U_OTROS']);
    unset($_SESSION['Print']);
    unset($_SESSION['U_PROF_APROB_RECO']);
    unset($_SESSION['aDataGirdAdj']);

    // D E T A L L E     D E S C R I P C I O N
    unset($_SESSION['aDataGird_INV_MRECO']);
    unset($_SESSION['aLabelGirdProd_INV_MRECO']);
    $aDataGrid  = $_SESSION['aDataGird_INV_MRECO'];

    $_SESSION['aLabelGirdProd_INV_MRECO'] = array(
        'Id',         'Bodega',       'Codigo Item',      'Descripcion',      'Unidad',       'Cantidad',         'Costo',        'Impuesto',
        'Dscto 1',    'Dscto 2',      'Dscto Gral',       'Total',            'Total Con Impuesto',               'Serie',        'Fecha Ela',
        'Fecha Cad',  'Detalle',      'Precio',           'Cuenta',           'Cuenta Impuesto',                  'Modificar',    'Eliminar',
        'Dmov'
    );

    $sql = "select mone_sgl_mone from saemone where
                    mone_cod_empr = $idempresa and
                    mone_cod_mone in ( select pcon_mon_base from saepcon where  pcon_cod_empr = $idempresa  ) ";
    $mone_sgl_mone = consulta_string_func($sql, 'mone_sgl_mone', $oIfx, '');
    unset($_SESSION['U_MONE_SIGLA']);
    $_SESSION['U_MONE_SIGLA'] = $mone_sgl_mone;

    switch ($sAccion) {
        case 'nuevo':
            // EMPRESA
            $sql = "select empr_cod_empr, empr_nom_empr from saeempr ";
            $lista_empr = lista_boostrap_func($oIfx, $sql, $idempresa, 'empr_cod_empr',  'empr_nom_empr');


            // Serie lote campos
            ///CHECK serie
            $ifu->AgregarCampoTexto('serie', "Serie |LEFT", true, '', 120, 120);
            ///CHECK lote
            $ifu->AgregarCampoTexto('lote', "Lote |LEFT", true, '', 120, 120);


            $ifu->AgregarCampoTexto('ruc', 'Identificacion|left', true, '', 120, 120);
            $ifu->AgregarCampoTexto('cliente_nombre', 'Suplidor|left', true, '', 250, 200);
            $ifu->AgregarComandoAlEscribir('cliente_nombre', 'autocompletar(' . $idempresa . ', event ); form1.cliente_nombre.value=form1.cliente_nombre.value.toUpperCase();');
            $lista_cliente = '<select class= "CampoFormulario" name="select" size="5" id="select" style="width: auto;display:none" onclick="envio_autocompletar();">
                                          </select>';
            $ifu->AgregarCampoTexto('cliente', 'Proveedor|left', true, '', 50, 50);
            $ifu->AgregarComandoAlPonerEnfoque('cliente', 'this.blur()');
            $ifu->AgregarComandoAlCambiarValor('cliente', 'cargar_datos()');
            $ifu->AgregarCampoTexto('cuenta_prove', 'Cuenta Prove|left', true, '', 50, 50);
            $ifu->AgregarCampoTexto('dir_prove', 'Direccion Prove|left', true, '', 250, 150);
            $ifu->AgregarCampoTexto('tel_prove', 'Telefono Prove|left', true, '', 250, 150);

            $ifu->AgregarCampoListaSQL('correo_prove', 'Email|left', "select emai_ema_emai from saeemai where 
                                            emai_cod_empr=$idempresa and emai_cod_sucu =$idsucursal and emai_cod_clpv = '$cliente'", false, 150);
            $ifu->AgregarCampoNumerico('codMinv', '|left', false, '', 70, 10);
            $ifu->AgregarComandoAlPonerEnfoque('codMinv', 'this.blur()');
            $ifu->AgregarCampoTexto('nota_compra', 'No. SECU|right', false, '', 100, 200);
            $ifu->AgregarComandoAlPonerEnfoque('nota_compra', 'this.blur()');

            $ifu->AgregarCampoFecha('fecha_pedido', 'Fecha Compra|left', true, date('Y') . '/' . date('m') . '/' . date('d'));
            $ifu->AgregarCampoFecha('fecha_entrega', 'Fecha Pago|left', true, date('Y') . '/' . date('m') . '/' . date('d'));

            $sql = "select t.tran_cod_tran, t.tran_des_tran  from saetran t, saedefi d  where
                                    t.tran_cod_tran = d.defi_cod_tran and
                                    t.tran_cod_empr = $idempresa and
                                    t.tran_cod_sucu = $idsucursal and
                                    t.tran_cod_modu = 10 and
                                    d.defi_cod_empr = $idempresa and
                                    d.defi_tip_defi = '0' and
                                    d.defi_cod_modu = 10  order by 2";
            $ifu->AgregarCampoLista('tran', 'Tipo|left', true, 170, 150);
            $lista_tran = lista_boostrap($oIfx, $sql, $tran_cod_tran, 'tran_cod_tran',  'tran_des_tran');

            $sql      = "select pcon_mon_base, pcon_seg_mone from saepcon where pcon_cod_empr = $idempresa ";
            $mone_cod = consulta_string_func($sql, 'pcon_mon_base', $oIfx, '');
            $ifu->AgregarCampoListaSQL('moneda', 'Moneda|left', "select  mone_cod_mone , mone_des_mone  from saemone where
                                                                                mone_cod_empr = $idempresa ", true, 80);
            $sql = "select  mone_cod_mone , mone_des_mone  from saemone where  mone_cod_empr = $idempresa";
            $lista_mone = lista_boostrap($oIfx, $sql, $mone_cod, 'mone_cod_mone',  'mone_des_mone');

            $ifu->AgregarCampoLista('tipo_factura', 'Tipo Factura|left', true, 180, 100);
            $ifu->AgregarOpcionCampoLista('tipo_factura', 'ELECTRONICA', 1);
            $ifu->AgregarOpcionCampoLista('tipo_factura', 'PREIMPRESA', 2);
            $ifu->AgregarComandoAlCambiarValor('tipo_factura', 'cargar_factura()');

            $ifu->AgregarCampoLista('tipo_retencion', 'Tipo Retencion|left', true, 180, 100);
            $ifu->AgregarOpcionCampoLista('tipo_retencion', 'ELECTRONICA', 'S');
            $ifu->AgregarOpcionCampoLista('tipo_retencion', 'PREIMPRESA', 'N');
            $ifu->AgregarComandoAlCambiarValor('tipo_retencion', 'cargar_secuencial_rete()');

            $ifu->AgregarCampoTexto('observaciones', 'Observaciones|left', false, '', 500, 1000);
            $ifu->AgregarCampoListaSQL('sucursal', 'Sucursal|left', "select sucu_cod_sucu, sucu_nom_sucu from saesucu where 
                                                sucu_cod_empr = $idempresa ", true, 'auto');
            $sql = "select sucu_cod_sucu, sucu_nom_sucu from saesucu where sucu_cod_empr = $idempresa";
            $lista_sucu = lista_boostrap($oIfx, $sql, $idsucursal, 'sucu_cod_sucu',  'sucu_nom_sucu');

            $sql = "select tidu_cod_tidu, 
                    tidu_des_tidu
                    from saetidu where
                    tidu_cod_empr = $idempresa ";
            $lista_docu = lista_boostrap($oIfx, $sql, $idsucursal, 'tidu_cod_tidu',  'tidu_des_tidu');

            $ifu->AgregarComandoAlCambiarValor('sucursal', 'cargar_tran();cargar_bode();cargar_fpago();');

            $ifu->AgregarCampoNumerico('plazo', 'No Plazo|left', true, '', 35, 50);
            $ifu->AgregarCampoTexto('contri_prove', 'Contribuyente Especial|left', true, '', 50, 100);

            // AUTORIZACION DEL PROVEEDOR
            /* $ifu->AgregarCampoTexto('auto_prove', 'No Autorizacion|left', true, '', 250, 100);
              $ifu->AgregarCampoTexto('serie_prove', 'Serie|left', true, '', 50, 100);
              $ifu->AgregarComandoAlEscribir('serie_prove', 'auto_proveedor(' . $idempresa . ', event)');
              $ifu->AgregarCampoTexto('fecha_validez', 'Fecha Validez|left', true, date('Y') . '/' . date('m') . '/' . date('d'), 70, 100); */

            $ifu->AgregarCampoListaSQL('tipo_pago', 'Tipo Pago|left', "select tpago_cod_tpago,
                                                                                        (saetpago.tpago_cod_tpago||' '||saetpago.tpago_des_tpago) as tipo_pago
                                                                                        from saetpago where
                                                                                        tpago_cod_empr = $idempresa ", true, '130');

            $ifu->AgregarCampoListaSQL('forma_pago1', 'Forma de Pago|left', "SELECT saefpagop.fpagop_cod_fpagop,
                                                                                             (saefpagop.fpagop_cod_fpagop||' '||saefpagop.fpagop_des_fpagop) as fpagop
                                                                                             FROM saefpagop   where
                                                                                             fpagop_cod_empr = $idempresa ", true, '120');

            //
            // PRODUCTO
            $ifu->AgregarCampoTexto('producto', 'Producto|LEFT', false, '', 250, 200);
            $ifu->AgregarComandoAlEscribir('producto', 'autocompletar_producto(' . $idempresa . ', event, 1 )');
            $ifu->AgregarCampoTexto('codigo_producto', 'Cod. Prod|left', false, '', 120, 100);
            $ifu->AgregarComandoAlEscribir('codigo_producto', 'autocompletar_producto(' . $idempresa . ', event, 2)');
            $ifu->AgregarCampoTexto('codigo_barra', 'Cod. Barra|left', false, '', 120, 100);
            $ifu->AgregarComandoAlEscribir('codigo_barra', 'autocompletar_producto(' . $idempresa . ', event, 3)');

            $ifu->AgregarCampoNumerico('cantidad', 'Cantidad|LEFT', true, 1, 50, 40);
            $ifu->AgregarCampoNumerico('costo', 'Costo|LEFT', true, 0, 90, 40);
            $ifu->AgregarCampoNumerico('iva', 'Impuesto|LEFT', true, 0, 50, 40);
            $ifu->AgregarCampoListaSQL('bodega', 'Bodega|left', "select  b.bode_cod_bode, b.bode_nom_bode from saebode b, saesubo s where
                                                                                b.bode_cod_bode = s.subo_cod_bode and
                                                                                b.bode_cod_empr = $idempresa and
                                                                                s.subo_cod_empr = $idempresa and
                                                                                s.subo_cod_sucu = $idsucursal ", true, '130');
            $sql = "select  b.bode_cod_bode, b.bode_nom_bode from saebode b, saesubo s where
                            b.bode_cod_bode = s.subo_cod_bode and
                            b.bode_cod_empr = $idempresa and
                            s.subo_cod_empr = $idempresa and
                            s.subo_cod_sucu = $idsucursal";
            $lista_bode = lista_boostrap($oIfx, $sql, $idbodega_s, 'bode_cod_bode',  'bode_nom_bode');

            $ifu->AgregarCampoTexto('cuenta_inv', 'Cuenta|LEFT', false, '', 100, 100);
            $ifu->AgregarCampoTexto('cuenta_iva', 'Cuenta Iva|LEFT', false, '', 100, 100);
            $ifu->AgregarCampoNumerico('desc1', 'Descto1|LEFT', true, 0, 50, 40);


            $op = '';
            unset($_SESSION['aDataGird_INV_MRECO']);
            unset($_SESSION['aDataGird_Pago']);
            $cont = count($aDataGird);
            if ($cont > 0) {
                $sHtml2 = mostrar_grid();
            } else {
                $sHtml2 = "";
            }

            $oReturn->assign("divFormularioDetalle", "innerHTML", $sHtml2);
            $oReturn->assign("divFormularioDetalle_FP", "innerHTML", $sHtml2);
            $oReturn->assign("divFormularioDetalleRET", "innerHTML", $sHtml2);
            $oReturn->assign("divTotal", "innerHTML", "");

            // control
            $fu->AgregarCampoOculto('ctrl', 'Control');
            $fu->cCampos["ctrl"]->xValor = 1;
            $ifu->cCampos["sucursal"]->xValor = $idsucursal;
            $ifu->cCampos["moneda"]->xValor = 1;


            // F O R M A    D E    P A G O
            unset($_SESSION['aDataGird_Pago']);
            $aDataGrid_Pago = $_SESSION['aDataGird_Pago'];
            $cont = count($aDataGrid_Pago);
            if ($cont > 0) {
                $sHtml2 = mostrar_grid_fp();
            } else {
                $sHtml2 = "";
            }

            $oReturn->assign("divFormularioDetalle_FP", "innerHTML", $sHtml2);
            $oReturn->assign("divFormularioDetalleFP_DET", "innerHTML", "");
            $oReturn->assign("divTotalFP", "innerHTML", "");

            $ifu->AgregarCampoListaSQL('forma_pago_prove', 'Forma de Pago|LEFT', "select  fpag_cod_fpag, fpag_des_fpag  from saefpag where
                                                                                            fpag_cod_empr = $idempresa and
                                                                                            fpag_cod_modu = 10 and
                                                                                            fpag_cod_sucu = $idsucursal	", false, 'auto');

            $sql = "select  fpag_cod_fpag, fpag_des_fpag  from saefpag where
                        fpag_cod_empr = $idempresa and
                        fpag_cod_modu = 10 and
                        fpag_cod_sucu = $idsucursal	 ";
            $lista_fp = lista_boostrap($oIfx, $sql, '', 'fpag_cod_fpag',  'fpag_des_fpag');

            $ifu->AgregarComandoAlCambiarValor('forma_pago_prove', 'tipo_fp();');
            $fu->AgregarCampoFecha('fecha_inicio', 'Fecha|left', true, date('Y') . '/' . date('m') . '/' . date('d'));
            $fu->AgregarCampoNumerico('dias_fp', 'No- Dias|left', true, 0, 25, 4);
            $fu->AgregarComandoAlCambiarValor('dias_fp', 'calculo_fecha_fp()');
            $fu->AgregarCampoTexto('fecha_final', 'Fecha Final|left', true, date('Y') . '/' . date('m') . '/' . date('d'), 70, 20);
            $fu->AgregarComandoAlPonerEnfoque('fecha_final', 'this.blur()');
            $fu->AgregarCampoNumerico('porcentaje', 'Porcentaje|left', true, 100, 40, 3);
            $fu->AgregarCampoNumerico('valor', 'Valor|left', true, 0, 100, 10);
            $fu->AgregarCampoNumerico('ingreso', 'Ingreso|left', true, 0, 100, 10);
            $fu->AgregarCampoTexto('tipo_fp_tmp', 'tipo_fp_tmp', false, '', 80, 10);
            $fu->AgregarCampoTexto('total_fact_fp', 'Total FP|left', false, 0, 100, 10);
            $fu->AgregarComandoAlPonerEnfoque('total_fact_fp', 'this.blur()');

            $ifu->AgregarCampoListaSQL('ccosn', 'Centro de Costo|left', "select ccosn_cod_ccosn,  ccosn_nom_ccosn
                                                                from saeccosn where
                                                                ccosn_cod_empr = $idempresa and
                                                                ccosn_mov_ccosn = 1 order by 2", false, 120);
            $sql = "select ccosn_cod_ccosn,  ccosn_nom_ccosn
                        from saeccosn where
                        ccosn_cod_empr = $idempresa and
                        ccosn_mov_ccosn = 1 order by 2";
            $lista_ccosn = lista_boostrap($oIfx, $sql, '', 'ccosn_cod_ccosn',  'ccosn_nom_ccosn');

            $diaHoy = date("Y-m-d");
            //$oReturn->alert($cliente);
            $sHtml_Fp = '<table align="left" class="table table-striped table-condensed" style="width: 60%; margin-bottom: 0px;">
                                   <tr><td colspan="4" align="center" class="bg-primary">FORMAS DE PAGO ONLINE</td></tr>';
            $sHtml_Fp .= '<tr>
                                <td class="total_fact"  bgcolor="#EBEBEB" height="25px">TOTAL: </td>
                                <td colspan="2" class="total_fact">
                                        <input type="text" class="form-control input-sm" id="total_fact_fp" name="total_fact_fp" style="width:150px; text-align:right"  readonly/> 
                                </td>
                          </tr>';
            $sHtml_Fp .= '<tr>
                                            <td class="labelFrm" >' . $ifu->ObjetoHtmlLBL('forma_pago_prove') . '</td>
                                            <td colspan="3">
                                                <select id="forma_pago_prove" name="forma_pago_prove" class="form-control input-sm" onchange="tipo_fp();">
                                                    <option value="0">Seleccione una opcion..</option>
                                                    ' . $lista_fp . '
                                                </select>
                                            </td>
                                   </tr>';
            $sHtml_Fp .= '<tr>
                                            <td class="labelFrm">' . $fu->ObjetoHtmlLBL('fecha_inicio') . '</td>
                                            <td colspan="3">
                                                <table width="99%">
                                                    <tr>
                                                        <td><input type="date" name="fecha_inicio" id="fecha_inicio" step="1" value="' . $diaHoy . '">    &nbsp;&nbsp;&nbsp;&nbsp;</td>
                                                        <td>*No Dias:</td>
                                                        <td>
                                                            <input type="number" class="form-control input-sm" id="dias_fp" name="dias_fp" style="width:150px; text-align:right"  onchange="calculo_fecha_fp();" /> 
                                                        </td>
                                                        <td>Fecha Final:</td>
                                                        <td><input type="date" name="fecha_final"  id="fecha_final" step="1" value="' . $diaHoy . '" onchange="fecha_pago(2);"></td>
                                                    </tr>
                                                </table>
                                            </td>
                                    </tr>';
            $sHtml_Fp .= '<tr>
                                            <td class="labelFrm">' . $fu->ObjetoHtmlLBL('valor') . '</td>
                                            <td colspan="3">
                                                <table  width="99%" border="0">
                                                    <tr>
                                                        <td width="43%"><input type="number" class="form-control input-sm" id="valor" name="valor" style="width:150px; text-align:right"  /></td>
                                                        <td class="labelFrm" width="29%">' . $fu->ObjetoHtmlLBL('porcentaje') . '</td>
                                                        <td>
                                                        <input type="number" class="form-control input-sm" id="porcentaje" name="porcentaje" style="width:150px; text-align:right" value="100" />
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                    </tr>';
            $sHtml_Fp .= '<tr style="display:none">
                                            <td colspan="2">' . $fu->ObjetoHtml('tipo_fp_tmp') . '</td>
                                   </tr>';
            $sHtml_Fp .= '<tr>
                                            <td colspan="4" align="center">                                                            
                                                    <div class="btn btn-primary btn-sm"onclick="javascript:anadir_detalle_fp(' . $idsucursal . ')">
                                                            <span class="glyphicon glyphicon-th-list"></span>
                                                            A&ntilde;adir
                                                    </div>							
                                            </td>
                                   </tr';
            $sHtml_Fp .= '</table>';

            // OTROS
            $sql = "select  rcgo_cod_rcgo, rcgo_des_rcgo, rcgo_cta_debi ,
                                    ( select  cuen_nom_cuen  from saecuen where
                                            cuen_cod_empr = $idempresa and
                                            cuen_cod_cuen = rcgo_cta_debi ) as cuenta
                                    from saercgo where
                                    rcgo_cod_empr = $idempresa ";
            unset($array_otros);
            if ($oIfx->Query($sql)) {
                if ($oIfx->NumFilas() > 0) {
                    do {
                        $array_otros[] = array($oIfx->f('rcgo_cod_rcgo'), $oIfx->f('rcgo_des_rcgo'), $oIfx->f('rcgo_cta_debi'), $oIfx->f('cuenta'));
                    } while ($oIfx->SiguienteRegistro());
                }
            }
            $oIfx->Free();

            $_SESSION['U_OTROS'] = $array_otros;

            $fu->AgregarCampoTexto('lote', 'Lote - Serie', false, '', 180, 100);
            $fu->AgregarCampoFecha('fecha_ela', 'Fecha Elaboracion|left', false, date('Y') . '/' . date('m') . '/' . date('d'));
            $fu->AgregarCampoFecha('fecha_cad', 'Fecha Caducidad|left', false, date('Y') . '/' . date('m') . '/' . date('d'));

            $fu->cCampos["fecha_ela"]->xValor = '';
            $fu->cCampos["fecha_cad"]->xValor = '';

            // RETENCION
            // DATOS RETENCION EMPRESA
            $sql = "select sucu_fac_elec from saesucu where sucu_cod_sucu = $idsucursal ";
            $sucu_fac_elec = consulta_string($sql, 'sucu_fac_elec', $oIfx, 'N');

            if ($sucu_fac_elec == 'S') {
                $tmp = " and retp_elec_sn = 'S'";
            } else {
                $tmp = " and retp_elec_sn = 'N'";
            }

            $sql = "select retp_sec_retp, retp_num_seri, retp_fech_cadu , retp_num_auto
							from saeretp where 
							retp_cod_empr = $idempresa and
							retp_cod_sucu = $idsucursal and
							retp_act_retp = '1' $tmp";
            //$oReturn->alert($sql);
            $num_rete     = consulta_string($sql, 'retp_sec_retp', $oIfx, '');
            $num_rete     = secuencial(2, '', $num_rete, 9);
            $seri_rete       = consulta_string($sql, 'retp_num_seri', $oIfx, '');
            $ret_fec_auto = fecha_mysql_func_(consulta_string($sql, 'retp_fech_cadu', $oIfx, date("Y-m-d")));
            $rete_auto    = consulta_string($sql, 'retp_num_auto', $oIfx, '');


            // $ifu->AgregarCampoTexto('num_rete', 'Retencion|left', true, $num_rete, 100, 100);			
            $fu->AgregarCampoSi_No('electronica', 'Electronica|left', $sucu_fac_elec);
            $fu->AgregarComandoAlCambiarValor('electronica', 'cargar_electronica();');
            //$ifu->AgregarComandoAlCambiarValor('num_rete', 'num_digito(1)');


            $ifu->AgregarCampoTexto('serie_rete', 'Serie|left', true, $seri_rete, 50, 100);
            $ifu->AgregarCampoTexto('auto_rete', 'Autorizacion|left', true, $rete_auto, 200, 100);
            $ifu->AgregarCampoTexto('cad_rete', 'Caducidad|left', true, $ret_fec_auto, 100, 100);

            /*$sql = "select retp_sec_retp, retp_num_seri, retp_fech_cadu from saeretp where 
							retp_cod_empr = $idempresa and
							retp_cod_sucu = $idsucursal and
							retp_act_retp = '1' ";
			//$oReturn->alert($sql);
            $num_rete = consulta_string($sql, 'retp_sec_retp', $oIfx, '');
            $num_rete = secuencial(2, '', $num_rete, 9);
			*/

            $ifu->AgregarCampoTexto('cod_ret', 'Cta Ret.|left', false, '', 100, 200);
            $ifu->AgregarComandoAlEscribir('cod_ret', 'cod_retencion(' . $idempresa . ', event );');
            $ifu->AgregarCampoNumerico('ret_porc', 'Porc.(%)|left', false, '', 50, 50);
            $ifu->AgregarCampoNumerico('ret_base', 'Base Imponible|left', false, '', 100, 200);
            $ifu->AgregarCampoNumerico('ret_val', 'Valor|left', false, '', 50, 200);
            $ifu->AgregarCampoNumerico('ret_num', 'N.- Retencion|left', false, $num_rete, 100, 200);
            $ifu->AgregarComandoAlCambiarValor('ret_num', 'cargar_digito_ret();');

            $ifu->AgregarCampoTexto('ejercicio', 'Ejercicio|right', false, '', 100, 200);
            $ifu->AgregarCampoTexto('periodo', 'Periodo|right', false, '', 100, 200);
            $ifu->AgregarCampoTexto('asiento', 'Asiento|right', false, '', 100, 200);

            $ifu->AgregarCampoSi_No('ret_asumido', 'Retencion Asumida|left', 'N');


            // moneda
            $ifu->AgregarCampoNumerico('cotizacion', 'Tipo Cambio|left', false, 1, 70, 9);

            $ifu->AgregarCampoNumerico('cotizacion_ext', 'Tipo Cambio Ext.|left', false, 1, 70, 9);
            $ifu->AgregarComandoAlPonerEnfoque('cotizacion_ext', 'this.blur()');

            $ifu->AgregarCampoListaSQL('moneda', 'Moneda|left', "select mone_cod_mone, mone_des_mone  from saemone where mone_cod_empr = $idempresa ", true, 150, 150);
            $ifu->AgregarComandoAlCambiarValor('moneda', 'cargar_coti();');


            $ifu->cCampos["moneda"]->xValor = $mone_cod;

            // COTIZACION MONEDA EXTRANJERA
            $sql      = "select pcon_mon_base, pcon_seg_mone from saepcon where pcon_cod_empr = $idempresa ";
            $mone_extr = consulta_string_func($sql, 'pcon_seg_mone', $oIfx, '');
            $sql = "select tcam_val_tcam from saetcam where
						mone_cod_empr = $idempresa and
						tcam_cod_mone = $mone_extr and
						tcam_fec_tcam in (
											select max(tcam_fec_tcam)  from saetcam where
													mone_cod_empr = $idempresa and
													tcam_cod_mone = $mone_extr
										)  ";

            $coti = consulta_string($sql, 'tcam_val_tcam', $oIfx, 0);
            $ifu->cCampos["cotizacion_ext"]->xValor = $coti;

            break;
    }

    $diaHoy = date("Y-m-d");
    $ultimo_dia_mes = date("Y-m-t", strtotime($diaHoy));


    $sHtml .= '<table class="table table-condensed table-striped" style="width: 99%; margin:0px;" align="center">
                    <tr>
                            <td>							
									<div class="btn btn-primary btn-sm" onclick="genera_formulario();">
										<span class="glyphicon glyphicon-file"></span>
										Nuevo
									</div>
									
                                    <div id ="imagen1" class="btn btn-primary btn-sm" onclick="guardar_precios(' . $opcion_tmp . ');">
										<span class="glyphicon glyphicon-floppy-disk"></span>
										Guardar
									</div>


									<div class="btn btn-primary btn-sm"onclick="javascript:generar_pdf_doc();">
										<span class="glyphicon glyphicon-print"></span>
										Impresion Movimiento
									</div>
									
									<div class="btn btn-primary btn-sm"onclick="javascript:impresion_asto();">
										<span class="glyphicon glyphicon-print"></span>
										Comprobante
									</div>									
									
									<div class="btn btn-primary btn-sm"onclick="javascript:formulario_etiqueta();">
										<span class="glyphicon glyphicon-print"></span>
										Etiquetas
									</div>							
									
									<div class="btn btn-primary btn-sm"onclick="javascript:orden_compra_consulta();">
										<span class="glyphicon glyphicon-tag"></span>
										Orden de Compra
									</div>	
									
									<div class="btn btn-primary btn-sm" onclick="archivosAdjuntos();">
										<span class="glyphicon glyphicon-folder-open"></span>
										Adjuntos
									</div>
									
                            </td>
                            
                            <td valing="top">
                                    <div class="form-inline">
                                        <label>Clave Acceso:</label>
                                        
                                        <input type="text" class="form-control input-sm" id="clave_acceso_" name="clave_acceso_" 
                                        value="" style="width:200px; text-align:right; height:25px"/>
                                        <div class="btn btn-success btn-sm" onclick="clave_acceso_sri(1);">
											<span class="glyphicon glyphicon-retweet"></span>
											Generar
										</div>
                                    </div>							
						    </td>
							
							<td align="right">
								<div class="btn btn-danger btn-sm"onclick="javascript:cancelar_pedido();">
									<span class="glyphicon glyphicon-remove"></span>
									Cancelar
								</div>
							</td>
                    </tr>
              </table>';

    $sHtml .= '<table class="table table-condensed table-striped" style="width: 99%; margin: 0px;" align="center">
				<tr>
					<td colspan="8" align="center" class="bg-primary">INVENTARIO COMPRA ONLINE</td>
				</tr>
				<tr class="msgFrm">
					<td colspan="8" align="center">Los campos con * son de ingreso obligatorio</td>
				</tr>';
    $sHtml .= '<tr>						
                    <td class="pedido" align="center" class="fecha_letra" style="color: red; font-size: 13px; margin:0px;" colspan="8">
                        <table>
                            <tr>
                                <td style="color: red; font-size: 12px; font-weight: bold;">
                                    ' . $ifu->ObjetoHtmlLBL('nota_compra') . '                                        
                                </td>
                                <td>
                                    <input type="text" class="form-control input-sm" id="codMinv" name="codMinv" size="0" readonly style="width:80px; text-align:right" />
                                </td>
                                <td>
                                    <input type="text" class="form-control input-sm" id="nota_compra" name="nota_compra" size="0" readonly/>
                                </td>
                            </tr>
                        </table>
                    </td>	
			   </tr>';
    $sHtml .= '<tr>
					<td>' . $ifu->ObjetoHtmlLBL('cliente_nombre') . '</td>
					<td colspan="7">
						<table class="table table-striped table-condensed" style="width: 98%; margin:0px;" align="center">
							<tr>
                                <td>
                                    <input type="text" class="form-control input-sm" id="cliente" name="cliente" style="width:50px; text-align:rigth" readonly/>
                                </td>
                                <td>
                                    <input type="text" class="form-control input-sm" id="cliente_nombre" name="cliente_nombre" onkeyup="autocompletar(' . $idempresa . ', event );" style="width:250px; text-align:left"/>
                                </td>
								<td>' . $ifu->ObjetoHtmlLBL('sucursal') . '</td>
                                <td>
                                    <select id="sucursal" name="sucursal" class="form-control input-sm" onchange="cargar_bodega();">
                                        <option value="0">Seleccione una opcion..</option>
                                        ' . $lista_sucu . '
                                    </select>
                                </td>
								<td>' . $ifu->ObjetoHtmlLBL('tran') . '</td>
                                <td>
                                    <select id="tran" name="tran" class="form-control input-sm" style="width:180px;" requerid>
                                        <option value="">Seleccione una opcion..</option>
                                        ' . $lista_tran . '
                                    </select>
                                </td>
								<td>' . $ifu->ObjetoHtmlLBL('moneda') . '</td>
                                <td>
                                        <select id="moneda" name="moneda" class="form-control input-sm" onchange="cotizacion();" style="width:180px;">
                                            <option value="0">Seleccione una opcion..</option>
                                            ' . $lista_mone . '
                                        </select>
                                </td>
								<td>' . $ifu->ObjetoHtmlLBL('cotizacion') . '</td>
                                <td>
                                    <input type="text" class="form-control input-sm" id="cotizacion" name="cotizacion" value="' . $coti . '" style="width:80px; text-align:right"/>
                                </td>
								<td style="display:none">' . $ifu->ObjetoHtml('cotizacion_ext') . '</td>
							</tr>
						</table>
					</td>					
				</tr>';
    $sHtml .= '<tr>
                    <td>' . $ifu->ObjetoHtmlLBL('ruc') . '</td>
                    <td colspan="7">
						<table class="table table-striped table-condensed" style="width: 98%; margin:0px;" align="center" >
                            <tr>
                                <td><input type="text" class="form-control input-sm" id="ruc" name="ruc" style="width:150px; height:25px; text-align:right" /></td>
                                <td>' . $ifu->ObjetoHtmlLBL('correo_prove') . '</td>
                                <td>
                                    <select id="correo_prove" name="correo_prove" class="form-control input-sm">
                                        <option value="0">Seleccione una opcion..</option>
                                    </select>
                                </td>
                                <td>' . $ifu->ObjetoHtmlLBL('fecha_pedido') . '</td>
                                <td> <input type="date" name="fecha_pedido" id="fecha_pedido" step="1" value="' . $diaHoy . '"></td>
                                <td>' . $ifu->ObjetoHtmlLBL('plazo') . '</td>
                                <td><input type="text" class="form-control input-sm" id="plazo" name="plazo" style="width:70px; height:25px; text-align:right" />  </td>
                                <td>' . $ifu->ObjetoHtmlLBL('fecha_entrega') . '</td>
                                <td> <input type="date" name="fecha_entrega" id="fecha_entrega" step="1" value="' . $diaHoy . '"></td>   
                            </tr>
                        </table>
                    </td>					
				</tr>';
    $sHtml .= '<tr>		
                    <td>' . $ifu->ObjetoHtmlLBL('tipo_factura') . '</td>
                    <td colspan="7">
                        <table class="table table-striped table-condensed" style="width: 98%; margin:0px;" align="center" >
                            <tr>
                                <td>
                                    <select id="tipo_factura" name="tipo_factura" class="form-control input-sm" onchange="cargar_factura();">
                                        <option value="0">Seleccione una opcion..</option>
                                        <option value="1">ELECTRONICA</option>
                                        <option value="2">PREIMPRESA</option>
                                    </select>
                                </td>
                                <td style="display:none">' . $ifu->ObjetoHtmlLBL('tipo_pago') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtml('tipo_pago') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtmlLBL('forma_pago1') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtml('forma_pago1') . '</td>      
                                <td>
                                    <table id="divFactura" class="table table-striped table-condensed" style="width: 100%; margin:0px;"></table>
                                </td>                          
                            </tr>
                        </table>
                    </td>
			   </tr>';
    $sHtml .= '<tr>
					<td>' . $ifu->ObjetoHtmlLBL('observaciones') . '</td>
                    <td colspan="7">
						<table class="table table-striped table-condensed" style="width: 98%; margin:0px;" align="center" >
                            <tr>
                                <td>
                                    <input type="text" class="form-control input-sm" id="observaciones" name="observaciones" style="width:80%; height:25px; text-align:left !important" />
                                </td>
                                <td>
                                    <div class="btn btn-primary btn-sm"onclick="javascript:cargar_oc();">
										<span class="glyphicon glyphicon-tag"></span>
										Orden de Compra
									</div>
                                </td>
                            </tr>
                        </table>
					</td>
				</tr>';

    $sHtml .= '<tr>
					<td style="display:none">' . $ifu->ObjetoHtml('cuenta_prove') . '</td>
					<td style="display:none">' . $ifu->ObjetoHtml('dir_prove') . '</td>
					<td style="display:none">' . $ifu->ObjetoHtml('tel_prove') . '</td>
					<td style="display:none">' . $fu->ObjetoHtml('ctrl') . '</td>
					<td style="display:none">' . $ifu->ObjetoHtml('contri_prove') . '</td>
				</tr>';
    $sHtml .= '</table>';


    $sHtml .= '<table class="table table-striped table-condensed" style="width: 99%; margin:0px;" align="center">
					<tr>
                        <td>' . $ifu->ObjetoHtmlLBL('bodega') . '</td>
                        <td>
                            <select id="bodega" name="bodega" class="form-control input-sm">
                                <option value="0">Seleccione una opcion..</option>
                                ' . $lista_bode . '
                            </select>
                        </td>
                        <td>' . $ifu->ObjetoHtmlLBL('producto') . '</td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="producto"  style="width: 200px; height:25px;"
                            id="producto" name="producto" onkeyup="autocompletar_producto(' . $idempresa . ', event, 1 );">
                        </td>
                        <td>' . $ifu->ObjetoHtmlLBL('codigo_producto') . '</td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="CODIGO"  style="width: 100px; height:25px; "
                            id="codigo_producto" name="codigo_producto" onkeyup="autocompletar_producto(' . $idempresa . ', event, 2 );">
                        </td>
						<td>' . $ifu->ObjetoHtmlLBL('codigo_barra') . '</td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="CODIGO BARRAS"  style="width: 100px; height:25px; "
                            id="codigo_barra" name="codigo_barra" onkeyup="autocompletar_producto(' . $idempresa . ', event, 3 );">
                        </td>						
                        <td>' . $ifu->ObjetoHtmlLBL('cantidad') . '</td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="Cantidad" id="cantidad" name="cantidad" style="width:80px; height:25px; text-align:right">
                        </td>
                        <td><a href="#" onclick="generaReporteCompras();">' . $ifu->ObjetoHtmlLBL('costo') . '</a></td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="Costo" id="costo" name="costo" style="width:80px; height:25px; text-align:right">
                        </td>
                        <td>' . $ifu->ObjetoHtmlLBL('iva') . '</td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="Impuesto" id="iva" name="iva" style="width:50px; height:25px; text-align:right">
                        </td>
						<td>' . $ifu->ObjetoHtmlLBL('desc1') . '</td>
                        <td>
                            <input class="form-control input-sm" type="text" placeholder="Dscto" id="desc1" name="desc1" style="width:50px; height:25px; text-align:right">
                        </td>
                        <td style="display:none">' . $ifu->ObjetoHtml('cuenta_inv') . '</td>
                        <td style="display:none">' . $ifu->ObjetoHtml('cuenta_iva') . '</td>
						<td style="display:none">' . $ifu->ObjetoHtml('ejercicio') . '</td>
						<td style="display:none">' . $ifu->ObjetoHtml('periodo') . '</td>
						<td style="display:none">' . $ifu->ObjetoHtml('asiento') . '</td>
					</tr>
				</table>';

    $sHtml .= '<table class="table table-striped table-condensed" style="width: 98%; margin:0px;" align="center">
					<tr>
						<td>' . $ifu->ObjetoHtmlLBL('ccosn') . '</td>
                        <td>
                            <select id="ccosn" name="ccosn" class="form-control input-sm" style="width:140px;">
                                <option value="0">Seleccione una opcion..</option>
                                ' . $lista_ccosn . '
                            </select>
                        </td>
                        <td><div id="lote_etiq">' . $fu->ObjetoHtmlLBL('lote') . '</div></td>
                        <td><div id="lote_txt"><input class="form-control input-sm" type="text" placeholder="Serie" id="lote" name="lote" style="width:150px; height:25px; text-align:right"></div></td>
                        <td><div id="fela_etiq">' . $fu->ObjetoHtmlLBL('fecha_ela') . '                                 </div></td>
                        <td><div id="fela_txt" ><input type="date" name="fecha_ela" step="1" value="' . $diaHoy . '">       </div></td>
                        <td><div id="fcad_txt" >' . $fu->ObjetoHtmlLBL('fecha_cad') . '                                    </div></td>
                        <td><div id="fcad_etiq"><input type="date" name="fecha_cad" step="1" value="' . $diaHoy . '">       </div></td>                        
                        <td> 
							<div class="btn btn-success btn-sm"onclick="javascript:cargar_producto();">
								<span class="glyphicon glyphicon-plus-sign"></span>
								Agregar Producto
							</div>
                        </td>
					</tr>
				</table>';
    // RETENCION
    $sHtml_ret .= '<table class="table table-striped table-condensed" style="width: 90%;  margin:0px;">
	               <tr><td colspan="4" align="center" class="bg-primary">RETENCIONES</td></tr>';
    $sHtml_ret .= '<tr>
						<td colspan="4" width="95%">
							<table class="table table-striped table-condensed" style="width: 98%;  margin:0px;">
								<tr>
									<td>' . $fu->ObjetoHtmlLBL('electronica') . '</td>
									<td>' . $fu->ObjetoHtml('electronica') . '</td>
									<td>' . $ifu->ObjetoHtmlLBL('serie_rete') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="SERIE" id="serie_rete" name="serie_rete" style="width:120px; height:25px; text-align:right" value="' . $seri_rete . '">
                                    </td>
									<td>' . $ifu->ObjetoHtmlLBL('cad_rete') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="CADUCIDAD" id="cad_rete" name="cad_rete" style="width:120px; height:25px; text-align:right" value="' . $ret_fec_auto . '">
                                    </td>  
									<td>' . $ifu->ObjetoHtmlLBL('auto_rete') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="AUTORIZACION" id="auto_rete" name="auto_rete" style="width:150px; height:25px; text-align:right" value="' . $rete_auto . '">
                                    </td>
									<td>' . $ifu->ObjetoHtmlLBL('ret_num') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="RETENCION" id="ret_num" name="ret_num" style="width:150px; height:25px; text-align:right" value="' . $num_rete . '" onchange="cargar_digito_ret();">
                                    </td>
								</tr>
								<tr>
									<td>' . $ifu->ObjetoHtmlLBL('cod_ret') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="CODIGO" id="cod_ret" name="cod_ret" style="width:120px; height:25px; text-align:right"  onkeyup="cod_retencion(' . $idempresa . ', event );">
                                    </td>   
									<td>' . $ifu->ObjetoHtmlLBL('ret_porc') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="PORCENTAJE" id="ret_porc" name="ret_porc" style="width:120px; height:25px; text-align:right" >
                                    </td>
									<td>' . $ifu->ObjetoHtmlLBL('ret_base') . '</td>
                                    <td>
                                        <input class="form-control input-sm" type="text" placeholder="BASE" id="ret_base" name="ret_base" style="width:120px; height:25px; text-align:right" >    
                                    </td>
									<td colspan="2">' . $ifu->ObjetoHtmlLBL('ret_asumido') . '</td>
									<td>' . $ifu->ObjetoHtml('ret_asumido') . '</td>
									<td align="center">												
											<div class="btn btn-success btn-sm"onclick="javascript:anadir_ret();">
												<span class="glyphicon glyphicon-plus-sign"></span>
												Agregar
											</div>
									</td>
								</tr>
						</td>
			   </tr>';
    $sHtml_ret .= '</table>';


    // FORM NUEVO
    $sHtml_cab .= '<div class="row">
                        <div class="col-md-12">
                            <div class="btn-group">
                                <div class="btn btn-primary btn-sm" onclick="genera_formulario();">
                                    <span class="glyphicon glyphicon-file"></span>
                                    Nuevo
                                </div>
                                
                                <div id ="imagen1" class="btn btn-primary btn-sm" onclick="guardar();">
                                    <span class="glyphicon glyphicon-floppy-disk"></span>
                                    Guardar
                                </div>

                                <div class="btn btn-primary btn-sm"onclick="javascript:generar_pdf_doc();">
                                    <span class="glyphicon glyphicon-print"></span>
                                    Impresion Movimiento
                                </div>                       
                            </div> 
                            
                        </div><br><br>';


    $sHtml_cab .= '<div class="col-md-12" style="margin-top: 5px !important">

                        <div class="form-row">
                            <div class="col-md-3">
                                <label for="empresa">Numero de Asiento:</label>
                                <input type="text" class="form-control input-sm" name="codigo_asto" id="codigo_asto" readonly>
                                <input type="text" class="form-control input-sm" name="cod_ejer" id="cod_ejer" style="display: none">
                                <input type="text" class="form-control input-sm" name="num_prdo" id="num_prdo" style="display: none">
                            </div>
                        </div>


                        <div class="form-row">
                            <div class="col-md-3">
                                <label for="empresa">* Empresa:</label>
                                <select id="empresa" name="empresa" class="form-control input-sm" onchange="cargar_sucursal();">
                                    <option value="0">Seleccione una opcion..</option>
                                    ' . $lista_empr . '
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sucursal">* Sucursal:</label>
                                <select id="sucursal" name="sucursal" class="form-control input-sm" required>
                                    <option value="">Seleccione una opcion..</option>
                                    ' . $lista_sucu . '
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_doc">* Tipo Documento:</label>
                                <select id="tipo_doc" name="tipo_doc" class="form-control input-sm" required>
                                    <option value="">Seleccione una opcion..</option>
                                    ' . $lista_docu . '
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_doc">* Fecha:</label>
                                <input type="date" class="form-control" id="fecha_registro" name="fecha_registro" />
                            </div>
                        </div>
                    </div>';


    $sHtml_cab .= '<div class="col-md-12">
                        <div class="form-row">
                            <div class="col-md-2">
                                <label class="control-label" for="fecha_cad"><div style="display:none"  id="fcad_txt" >' . $fu->ObjetoHtmlLBL('fecha_cad') . '</div></label>    
                                <div style="display:none" id="fcad_etiq"><input type="date" name="fecha_cad" id="fecha_cad" step="1" onchange="validar_fecha_caducidad();" class="form-control input-sm"></div>
                            </div>

                            <div class="col-md-12 text-center" style="margin-top: 50px; border: 2px solid black !important; padding: 30px; border-style: dotted !important;">
                                <div class="row justify-content-md-center">
                                    <div class="col-md-12" style="margin-bottom: 10px;">
                                        <label for="archivo">* Cargar Archivo con el Balance Inicial:</label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="file" name="archivo" id="archivo" onchange="upload_image(id);" required>
                                        <div class="upload-msg"></div>
                                    </div>
                                    <div class="col-md-4" style="text-align: center; align-content: center;">
                                        <div><label class="control-label"> Ejemplo:</label> </div>
                                        <div class="btn btn-sm">
                                            <span class="glyphicon glyphicon-file" style="text-align:left;"></span>
                                            <div style="text-align:left;">
                                                <a href="ejemplo.txt" download="Archivo Ejemplo Compra.txt" id="txt">
                                                    Ejemplo Archivo
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn btn-primary btn-sm" onclick="consultar();" style="width: 100%">
                                            <span class="glyphicon glyphicon-search"></span>
                                            Consultar
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                    </div>
                                </div>
                            </div>
                            <div style="display: none">


                                <td style="display:none">' . $ifu->ObjetoHtml('cuenta_inv') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtml('cuenta_iva') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtml('ejercicio') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtml('periodo') . '</td>
                                <td style="display:none">' . $ifu->ObjetoHtml('asiento') . '</td>
                            </div>
                        </div>
                    </div>';

    $sHtml_cab .= '</div>';


    $oReturn->assign("divFormularioCabecera", "innerHTML", $sHtml_cab);
    //$oReturn->assign("nota_pedido", "disabled", true);
    $oReturn->assign("divReporte", "innerHTML", "");
    $oReturn->assign("divAbono", "innerHTML", "");
    $oReturn->assign("cliente_nombre", "placeholder", "ESCRIBA EL CLIENTE O RUC Y PRESIONE F4 O ENTER...");
    $oReturn->assign("producto", "placeholder", "ESCRIBA EL PROD. Y PRESIONE F4 ....");
    $oReturn->assign("divFormularioFp", "innerHTML", $sHtml_Fp);
    $oReturn->assign("cliente_nombre", "focus()", "");
    $oReturn->assign("divFormularioRET", "innerHTML", $sHtml_ret);

    return $oReturn;
}

function cargar_ord_compra_respaldo($aForm = '')
{
    //Definiciones
    global $DSN_Ifx, $DSN;

    if (session_status() !== PHP_SESSION_ACTIVE) {session_start();}

    $oCon = new Dbo;
    $oCon->DSN = $DSN;
    $oCon->Conectar();

    $oIfx = new Dbo;
    $oIfx->DSN = $DSN_Ifx;
    $oIfx->Conectar();

    $ifu = new Formulario;
    $ifu->DSN = $DSN_Ifx;

    $oReturn = new xajaxResponse();


    $idempresa = $_SESSION['U_EMPRESA'];
    $idsucursal = $aForm['sucursal'];


    unset($_SESSION['U_PROD_COD_PRECIO']);

    //////////////

    try {

        // CENTRO DE COSTO
        $sql = "select ccosn_cod_ccosn,  ccosn_nom_ccosn
                from saeccosn where
                ccosn_cod_empr = $idempresa and
                ccosn_mov_ccosn = 1 order by 2";

        unset($array_ccosn);
        unset($array_ccosn_cod);
        $array_ccosn     = array_dato($oIfx, $sql, 'ccosn_nom_ccosn', 'ccosn_nom_ccosn');
        $array_ccosn_cod = array_dato($oIfx, $sql, 'ccosn_cod_ccosn', 'ccosn_nom_ccosn');

        // CUENTA CONTABLE
        $sql = "select cuen_cod_cuen,  cuen_nom_cuen
                from saecuen where
                cuen_cod_empr = $idempresa order by 2";

        unset($array_cuen);
        unset($array_cuen_cod);
        $array_cuen     = array_dato($oIfx, $sql, 'ccosn_nom_ccosn', 'ccosn_nom_ccosn');
        $array_cuen_cod = array_dato($oIfx, $sql, 'ccosn_cod_ccosn', 'ccosn_nom_ccosn');

        $archivo = $aForm['archivo'];

        // archivo txt
        $archivo_real = substr($archivo, 12);
        list($xxxx, $exten) = explode(".", $archivo_real);


        if ($exten == 'txt') {
            $nombre_archivo = "upload/" . $archivo_real;

            $file       = fopen($nombre_archivo, "r");
            $datos      = file($nombre_archivo);
            $NumFilas   = count($datos);

            $table_cab  = '<br><br>';
            $table_cab  = '<h4>Lista del archivo exportado</h4>';
            $table_cab .= '<table class="table table-bordered table-striped table-condensed" style="width: 98%; margin-bottom: 0px;">';
            $table_cab .= '<tr>
                                            <td class="success" style="width: 4.5%;">N.-</td>
                                            <td class="success" style="width: 4.5%;">CODIGO CUENTA</td>
                                            <td class="success" style="width: 4.5%;">NOMBRE CUENTA</td>
                                            <td class="success" style="width: 4.5%;">CODIGO C.ACTIVIDAD</td>
                                            <td class="success" style="width: 9.5%;">NOMBRE C.ACTIVIDAD</td>
                                            <td class="success" style="width: 4.5%;">CODIGO C.COSTOS</td>
                                            <td class="success" style="width: 4.5%;">NOMBRE C.COSTOS</td>
                                            <td class="success" style="width: 4.5%;">DEBITO</td>
                                            <td class="success" style="width: 4.5%;">CREDITO</td>
                                            <td class="success" style="width: 4.5%;">DETALLE</td>';
            $table_cab .= '</tr>';
            $x = 1;
            // $oReturn->alert('Buscando ...');
            unset($array);

            foreach ($datos as $val) {
                /* dasi_cod_cuen	dasi_cod_cact	ccos_cod_ccos	dasi_dml_dasi	dasi_cml_dasi	dasi_det_asi

                    */

                list(
                    $dasi_cod_cuen,    $dasi_cod_cact,    $ccos_cod_ccos,    $dasi_dml_dasi,    $dasi_cml_dasi,    $dasi_det_asi
                ) = explode("	", $val);

                if ($x > 1) {

                    $control_insert_array = true;

                    // CUENTA CONTABLE
                    $sql_cuen = "select cuen_cod_cuen,  cuen_nom_cuen
                    from saecuen where
                    cuen_cod_empr = $idempresa 
                    and cuen_cod_cuen = '" . trim($dasi_cod_cuen) . "'";
                    $cuen_nom_cuen = consulta_string_func($sql_cuen, 'cuen_nom_cuen', $oIfx, '');


                    // CENTRO ACTIVIDAD     
                    $sql_cact = "select cact_cod_cact,  cact_nom_cact
                    from saecact where
                    cact_cod_empr = $idempresa 
                    and cact_cod_cact = '" . trim($dasi_cod_cact) . "'";
                    $cact_nom_cact = consulta_string_func($sql_cact, 'cact_nom_cact', $oIfx, '');


                    // CENTRO DE COSTOS     
                    $sql_ccosn = "select ccosn_cod_ccosn,  ccosn_nom_ccosn
                    from saeccosn where
                    ccosn_cod_empr = $idempresa 
                    and ccosn_cod_ccosn = '" . trim($ccos_cod_ccos) . "'";
                    $ccosn_nom_ccosn = consulta_string_func($sql_ccosn, 'ccosn_nom_ccosn', $oIfx, '');



                    $table_cab .= '<tr>';
                    $table_cab .= '<td>' . ($x - 1) . '</td>';

                    // CUENTA CONTABLE
                    if (!empty($cuen_nom_cuen)) {
                        $table_cab .= '<td>' . $dasi_cod_cuen . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $dasi_cod_cuen . '</td>';
                        $control_insert_array = false;
                    }

                    if (!empty($cuen_nom_cuen)) {
                        $table_cab .= '<td>' . $cuen_nom_cuen . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $cuen_nom_cuen . '</td>';
                        $control_insert_array = false;
                    }

                    // CENTRO ACTIVIDAD
                    if (!empty($cact_nom_cact)) {
                        $table_cab .= '<td>' . $dasi_cod_cact . '</td>';
                    } else if (empty($dasi_cod_cact)) {
                        $table_cab .= '<td>' . $dasi_cod_cact . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $dasi_cod_cact . '</td>';
                        $control_insert_array = false;
                    }

                    if (!empty($cact_nom_cact)) {
                        $table_cab .= '<td>' . $cact_nom_cact . '</td>';
                    } else if (empty($dasi_cod_cact)) {
                        $table_cab .= '<td>' . $cact_nom_cact . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $cact_nom_cact . '</td>';
                        $control_insert_array = false;
                    }


                    // CENTRO DE COSTO
                    if (!empty($ccosn_nom_ccosn)) {
                        $table_cab .= '<td>' . $ccos_cod_ccos . '</td>';
                    } else if (empty($ccos_cod_ccos)) {
                        $table_cab .= '<td>' . $ccos_cod_ccos . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $ccos_cod_ccos . '</td>';
                        $control_insert_array = false;
                    }

                    if (!empty($ccosn_nom_ccosn)) {
                        $table_cab .= '<td>' . $ccosn_nom_ccosn . '</td>';
                    } else if (empty($ccos_cod_ccos)) {
                        $table_cab .= '<td>' . $ccosn_nom_ccosn . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $ccosn_nom_ccosn . '</td>';
                        $control_insert_array = false;
                    }


                    //$dasi_dml_dasi,    $dasi_cml_dasi,    $dasi_det_asi
                    // DEBITO
                    if (!empty($dasi_dml_dasi)) {
                        $table_cab .= '<td>' . $dasi_dml_dasi . '</td>';
                    } else if ($dasi_dml_dasi == 0) {
                        $table_cab .= '<td>' . $dasi_dml_dasi . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $dasi_dml_dasi . '</td>';
                        $control_insert_array = false;
                    }

                    // CREDITO
                    if (!empty($dasi_cml_dasi)) {
                        $table_cab .= '<td>' . $dasi_cml_dasi . '</td>';
                    } else if ($dasi_cml_dasi == 0) {
                        $table_cab .= '<td>' . $dasi_cml_dasi . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $dasi_cml_dasi . '</td>';
                        $control_insert_array = false;
                    }

                    // DETALLE
                    if (!empty($dasi_det_asi)) {
                        $table_cab .= '<td>' . $dasi_det_asi . '</td>';
                    } else {
                        $table_cab .= '<td style="background:yellow">' . $dasi_det_asi . '</td>';
                        $control_insert_array = false;
                    }


                    $table_cab .= '</tr>';



                    if ($control_insert_array == true) {

                        $dasi_dml_dasi = str_replace(",", ".", $dasi_dml_dasi);
                        $dasi_cml_dasi = str_replace(",", ".", $dasi_cml_dasi);

                        $array[] = array(
                            $dasi_cod_cuen,
                            $dasi_cod_cact,
                            $ccos_cod_ccos,
                            $dasi_dml_dasi,
                            $dasi_cml_dasi,
                            $dasi_det_asi
                        );
                    }


                    // $data = [
                    //     'dasi_cod_cuen' => $dasi_cod_cuen,	
                    //     'dasi_cod_cact' => $dasi_cod_cact,	
                    //     'ccos_cod_ccos' => $ccos_cod_ccos,	
                    //     'dasi_dml_dasi' => $dasi_dml_dasi,	
                    //     'dasi_cml_dasi' => $dasi_cml_dasi,	
                    //     'dasi_det_asi' =>  $dasi_det_asi
                    // ]
                    // array_push($array_save_balance, $data);
                }
                $x++;
            }

            $_SESSION['U_BALANCE_FILE'] = $array;

            $html_tabla .= $table_cab;
            $html_tabla .= "</table>";

            $oReturn->assign("divFormularioDetalle2", "innerHTML", $html_tabla);
        } else {
            $oReturn->script("Swal.fire({
                                            title: '<h3><strong>!!!!....Archivo Incorrecto, por favor subir Archivo con extension .txt...!!!!!</strong></h3>',
                                            width: 800,
                                            type: 'error',   
                                            timer: 3000   ,
                                            showConfirmButton: false
                                            })");
            $oReturn->assign("divFormularioDetalle2", "innerHTML", '');
        }
    } catch (Exception $ex) {
        $oReturn->alert($ex->getMessage());
    }

    $oReturn->script("jsRemoveWindowLoad();");
    return $oReturn;
}

// Guardar precios
function guardar($aForm = '')
{

    global $DSN, $DSN_Ifx;
    if (session_status() !== PHP_SESSION_ACTIVE) {session_start();}

    $oIfx = new Dbo();
    $oIfx->DSN = $DSN_Ifx;
    $oIfx->Conectar();

    $fu = new Formulario;
    $fu->DSN = $DSN;

    $oReturn = new xajaxResponse();

    $idempresa         = $aForm['empresa'];
    $idsucursal         = $aForm['sucursal'];
    $tidu_cod         = $aForm['tipo_doc'];
    // $fecha_mov    = date('Y-m-d');
    $fecha_mov    = $aForm['fecha_registro'];
    // $time         = date("Y-m-d H:i:s");
    $user_web     = $_SESSION['U_ID'];
    

    $balances    =  $_SESSION['U_BALANCE_FILE'];


    if (count($balances) > 0) {

        //      TRANSACCIONALIDAD IFX
        try {
            // commit
            $oIfx->QueryT('BEGIN WORK;');

            //MAYORIZACION
            $class = new mayorizacion_class();


            $detalleAsto = '';

            // TIDU COD MODU
            $sql    = "select tidu_cod_modu from saetidu where tidu_cod_tidu = '$tidu_cod' ";
            $modulo = consulta_string_func($sql, 'tidu_cod_modu', $oIfx, '', 0);

            $array = $class->secu_asto($oIfx, $idempresa, $idsucursal, $modulo, $fecha_mov, $user_web, $tidu_cod);
            foreach ($array as $val) {
                $secu_asto  = $val[0];
                $secu_dia   = $val[1];
                $tidu       = $val[2];
                $idejer     = $val[3];
                $idprdo     = $val[4];
                $moneda     = $val[5];
                $tcambio    = $val[6];
                $empleado   = $val[7];
                $usua_nom   = $val[8];
            } // fin foreach

            if (empty($clpv_nom)) {
                $clpv_nom = '';
            }

            $class->saeasto(
                $oIfx,
                $secu_asto,
                $idempresa,
                $idsucursal,
                $idejer,
                $idprdo,
                $moneda,
                $user_web,
                '',
                $clpv_nom,
                0,
                $fecha_mov,
                $detalleAsto,
                $secu_dia,
                $fecha_mov,
                $tidu,
                $usua_nom,
                $user_web,
                5
            );


            $total_debe = 0;
            $total_haber = 0;
            unset($array);
            foreach ($balances as $arreglo) {
                // $empl_cod = $arreglo[0];
                // $empl_nom = $arreglo[1];
                // $tipo_cta = $arreglo[2];
                // $num_cta = $arreglo[3];
                // $sueldo = $arreglo[4];
                // $empl_banc = $arreglo[5];
                // $cod_cta = $arreglo[6];
                // $empl_tip = $arreglo[7];
                // $empl_emai = $arreglo[8];

                $dasi_cod_cuen = $arreglo[0];
                $dasi_cod_cact = $arreglo[1];
                $ccos_cod_ccos = $arreglo[2];
                $dasi_dml_dasi = $arreglo[3];
                $dasi_cml_dasi = $arreglo[4];
                $dasi_det_asi = $arreglo[5];
                $total_debe = $total_debe + $dasi_dml_dasi;
                $total_haber = $total_haber + $dasi_cml_dasi;



                $cotiza = 1;
                $dasi_cod_ret = '';
                $dasi_dir = '';
                $dasi_cta_ret = '';
                $opBand = '';
                $opBacn = '';
                $opFlch = '';
                $num_cheq = '';

                $class->saedasi(
                    $oIfx,
                    $idempresa,
                    $idsucursal,
                    $dasi_cod_cuen,
                    $idprdo,
                    $idejer,
                    $ccos_cod_ccos,
                    $dasi_dml_dasi,
                    $dasi_cml_dasi,
                    $dasi_dml_dasi,
                    $dasi_cml_dasi,
                    $cotiza,
                    $dasi_det_asi,
                    '',
                    '',
                    $user_web,
                    $secu_asto,
                    $dasi_cod_ret,
                    $dasi_dir,
                    $dasi_cta_ret,
                    $opBand,
                    $opBacn,
                    $opFlch,
                    $num_cheq,
                    $dasi_cod_cact
                );
            }

            $sql = "update saeasto set asto_est_asto = 'MY', 
									asto_vat_asto = $total_debe  where
									asto_cod_empr = $idempresa  and
									asto_cod_sucu = $idsucursal and
									asto_cod_asto = '$secu_asto' and
									asto_cod_ejer = $idejer and
									asto_num_prdo = $idprdo and
									asto_cod_modu = 5 and
									asto_cod_empr = $idempresa and     
									asto_cod_sucu = $idsucursal and
									asto_user_web = $user_web ";
            $oIfx->QueryT($sql);

            $oReturn->assign("codigo_asto", "value", $secu_asto);
            $oReturn->assign("cod_ejer", "value", $idejer);
            $oReturn->assign("num_prdo", "value", $idprdo);


            $oIfx->QueryT('COMMIT WORK;');
            $oReturn->alert('Ingresado Correctamente...');
            $oReturn->script('buscar_nomina();');
        } catch (Exception $e) {
            // rollback
            $oIfx->QueryT('ROLLBACK WORK;');
            $oReturn->alert($e->getMessage());
            $oReturn->assign("ctrl", "value", 1);

            $oReturn->assign("codigo_asto", "value", '');
            $oReturn->assign("cod_ejer", "value", '');
            $oReturn->assign("num_prdo", "value", '');
        }
    } else {
        $oReturn->alert('Por suba un archivo ....');
        $oReturn->script('habilitar_boton();');
    }

    return $oReturn;
}

function consulta($sql, $campo, $Conexion)
{

    $total_mes_stock = 0;
    if ($Conexion->Query($sql)) {
        if ($Conexion->NumFilas() > 0) {
            $total_mes_stock = $Conexion->f($campo);
            if (empty($total_mes_stock)) {
                $total_mes_stock = 0;
            }
        } else {
            $total_mes_stock = 0;
        }
    }
    $Conexion->Free();
    //$Conexion->Desconectar();

    return $total_mes_stock;
}

function lista_boostrap($oIfx, $sql, $campo_defecto, $campo_id, $campo_nom)
{
    $optionEmpr = '';
    if ($oIfx->Query($sql)) {
        if ($oIfx->NumFilas() > 0) {
            do {
                $empr_cod_empr = $oIfx->f($campo_id);
                $empr_nom_empr = htmlentities($oIfx->f($campo_nom));

                $selectedEmpr = '';
                if ($empr_cod_empr == $campo_defecto) {
                    $selectedEmpr = 'selected';
                }

                $optionEmpr .= '<option value="' . $empr_cod_empr . '" ' . $selectedEmpr . '>' . $empr_nom_empr . '</option>';
            } while ($oIfx->SiguienteRegistro());
        }
    }
    $oIfx->Free();

    return $optionEmpr;
}

function genera_pdf_doc($idempresa, $idsucursal, $asto_cod, $ejer_cod, $prdo_cod)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {session_start();}
    global $DSN_Ifx;

    $oIfxA = new Dbo();
    $oIfxA->DSN = $DSN_Ifx;
    $oIfxA->Conectar();

    $oIfx = new Dbo;
    $oIfx->DSN = $DSN_Ifx;
    $oIfx->Conectar();
    unset($_SESSION['pdf']);
    $oReturn = new xajaxResponse();

    $diario = generar_diarios_ingresos_pdf($idempresa, $idsucursal, $asto_cod, $ejer_cod, $prdo_cod);
    $_SESSION['pdf'] = $diario;

    $oReturn->script('generar_pdf()');
    return $oReturn;
}

/* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */
/* PROCESO DE REQUEST DE LAS FUNCIONES MEDIANTE AJAX NO MODIFICAR */
$xajax->processRequest();
/* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */
