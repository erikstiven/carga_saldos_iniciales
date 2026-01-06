/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

    //apertura de ventana secundaria (popup) para emision de ventanas en formato PDF
    function ventanaSecundaria(URL,ventana,wi,he)
    {
        var opciones="toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=yes, width="+wi+", height="+he+"";
        window.open(URL,ventana,opciones);
        return false;
    }
    
//Funcion en tiempo real para bloquear el ingreso de caracteres diferentes a numeros
function solo_numero(e) { // 1
    tecla = (document.all) ? e.keyCode : e.which; // 2
    if ((tecla==8)||(tecla==9)||(tecla==10)||(tecla==11)||(tecla==0)) return true; // 3
    if (e.ctrlKey && tecla==86) { return true }; //Ctrl v
    if (e.ctrlKey && tecla==67) { return true }; //Ctrl c
    if (e.ctrlKey && tecla==88) { return true }; //Ctrl x
    patron =/[0-9\s]/; // 4
    te = String.fromCharCode(tecla); // 5
    return patron.test(te); // 6
}

//Funcion en tiempo real para bloquear el ingreso de caracteres diferentes a numeros y puntos "."
function solo_numero_2(e) { // 1
    tecla = (document.all) ? e.keyCode : e.which; // 2
    if ((tecla==8)||(tecla==9)||(tecla==10)||(tecla==11)||(tecla==0)) return true; // 3
    patron =/[0-9.\s]/; // 4
    te = String.fromCharCode(tecla); // 5
    return patron.test(te); // 6
}
function solo_numero_dec(e) { // 1
    tecla = (document.all) ? e.keyCode : e.which; // 2
    if ((tecla==8)||(tecla==9)||(tecla==10)||(tecla==11)||(tecla==0)) return true; // 3
    patron =/[0-9,\s]/; // 4
    te = String.fromCharCode(tecla); // 5
    return patron.test(te); // 6
}

//Funcion en tiempo real para bloquear el ingreso de caracteres diferentes a numeros y barras "/"
function numero_barra(e) { // 1
    tecla = (document.all) ? e.keyCode : e.which; // 2
    if ((tecla==8)||(tecla==9)||(tecla==10)||(tecla==11)||(tecla==0)) return true; // 3
    patron =/[0-9-\/\s]/; // 4
    te = String.fromCharCode(tecla); // 5
    return patron.test(te); // 6
}

//Funcion en tiempo real para bloquear el ingreso de caracteres diferentes a letras de la a-z
function solo_letras(e) { // 1
tecla = (document.all) ? e.keyCode : e.which; // 2
if ((tecla==8)||(tecla==9)||(tecla==10)||(tecla==11)||(tecla==0)) return true; // 3
patron =/^[a-zA-Z\s\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+$/; // 4
te = String.fromCharCode(tecla); // 5
return patron.test(te); // 6
}

//Funcion en tiempo real para agregar decimales a una cadena de caracteres
function number_format(id) {
    var valor;
    var cadena = document.getElementById(id).value;
    if(cadena.indexOf('.')<0){
        valor=cadena+".00";
    } else {
        var x=cadena.split('.');
        if (x[1].length==0){
            valor=x[0]+".00";
        } else if (x[1].length==1){
            valor=x[0]+"."+x[1]+"0";
        } else if (x[1].length==2){
            valor=x[0]+"."+x[1];
        } else if (x[1].length>2) {
            valor=cadena.substr(0,5);
        }
    }
    document.getElementById(id).value=valor;
}

//Metodo que permite cambiar el status de un campo para deshabilitar o habilitar en base de datos
//este metodo es utilizado con funciones de jquery y con la llamada a la pagina "funct.php" que se encarga de gestionar toda la informacion necesaria
function ActualizarStatus_2(valor, tabla, id, campo, campo_act){
        var dataString = 'valor='+valor+'&tabla='+tabla+'&id='+id+'&campo='+campo+'&campo_act='+campo_act
        $.ajax({
            type: "POST",
            url: "Utilidades/funct.php",
            data: dataString,
            success: function() 
                   {
                        alert("Status Actualizado exitosamente!");
                        if($('#StatusOculto'+id).val()==1)
                            {  
                                $('#Status'+id).attr('src','/rector/images/inactivo.png'); //si se deshabilita colocara un circulo rojo
                                $('#StatusOculto'+id).val(0);
                            }
                        else
                            {
                                $('#Status'+id).attr('src','/rector/images/activo.png'); //si se habilita colocara un circulo verde
                                $('#StatusOculto'+id).val(1);
                            }	 
                    },
                            //sino, error de informacion, no se podra actualizar el campo en base de datos
           error: function(data){ if (!data) alert("Error: No se pudo Actualizar"); else alert("Sesion cerrada exitosamente"); }
        });
    }
    
    //apertura de ventana secundaria (popup) para emision de ventanas en formato PDF
   
    var campos=0;

//FUNCIONES DINAMICAS QUE PERMITEN AGREGAR CADENAS DE CARACTERES EN TABLAS PARA POGRESIVAMENTE SER INSERTADAS EN BASE DE DATOS
//ESTAS FUNCIONES SON UTILIZADAS PARA AGREGAR LOS DIAGNOSTICOS, SOLICITUDES DE IMAGENES Y LABORATORIO, Y MEDICAMENTOS NECESARIOS PARA LOS PACIENTES
function agregar_borrar(id_valor, valor, input){
    // RECOJE VALORES DE INPUT
    var a=id_valor;
    var b=valor;
    var c=input;
    // BORRA VALORES DE INPUT PARA SER AGREGADOS EN LISTADO
    document.getElementById("id_medicamento").value='0';
    document.getElementById("medicamento").value='';
    document.getElementById("txtarea_desc").value='';
    agregarCampo(a,b,c);
}

function agregarCampo(id,medico,cons,consT,fecha,entrada,salida,fecha_fin,semanas,id_esp, esp){
  campos= document.getElementById('txtcontador').value;
  var cupos= document.getElementById('cupos_permitidos').value;
  if (id>0) {
      if (cons>0) {
           if (fecha!='') {
               if (entrada!='') {
                  if (salida!='') {
                      if (cupos>0) {
                            document.getElementById("div_dinamico_cb").style.display='block';
                            var NvoCampo= document.createElement("tr");
                            NvoCampo.id= "divcampo_"+(campos);
                            NvoCampo.innerHTML= 
                               "   <tr>" +
                               "     <td nowrap='nowrap' width='180px'>"+medico+
                               "        <input type='hidden' size='50' name='id_med" + campos + 
                                             "' id='id_med" + campos + "' value='"+id+"' readonly>" +
                               "     </td>" +
                               "     <td nowrap='nowrap' width='180px'>"+esp+
                               "        <input type='hidden' size='50' name='id_esp" + campos + 
                                             "' id='id_esp" + campos + "' value='"+id_esp+"' readonly>" +
                               "     </td>" +
                               "     <td nowrap='nowrap' width='110px'><div width='400px'>"+consT+
                               "        <input type='hidden' size='50' name='consul_" + campos + 
                                             "' id='consul_" + campos + "' value='"+cons+"' readonly>" +
                               "     </div></td>" +
                               "     <td nowrap='nowrap' width='85px'>"+fecha+
                               "        <input type='hidden' size='50' name='fecha_" + campos + 
                                             "' id='fecha_" + campos + "' value='"+fecha+"' readonly>" +
                               "     </td>" +
                               "     <td nowrap='nowrap' width='70px'>"+entrada+
                               "        <input type='hidden' size='50' name='entrada_" + campos + 
                                             "' id='entrada_" + campos + "' value='"+entrada+"' readonly>" +
                               "     </td>" +
                               "     <td nowrap='nowrap' width='70px'>"+salida+
                               "        <input type='hidden' size='50' name='salida_" + campos + 
                                             "' id='salida_" + campos + "' value='"+salida+"' readonly>" +
                               "     </td>" +
                               "     <td nowrap='nowrap' width='85px'>"+fecha_fin+
                               "        <input type='hidden' size='50' name='fecha_fin" + campos + 
                                             "' id='fecha_fin" + campos + "' value='"+fecha_fin+"' readonly>" +
                               "        <input type='hidden' size='50' name='cant_semanas_" + campos + 
                                             "' id='cant_semanas_" + campos + "' value='"+semanas+"' readonly>" +
                               "        <input type='hidden' size='50' name='cupos_" + campos + 
                                             "' id='cupos_" + campos + "' value='"+cupos+"' readonly>" +
                               "     </td>" +
                               "     <td nowrap='nowrap' style='text-align:center'>" +
                               "        <a href='JavaScript:quitarCampo(" + campos +");'> Quitar </a>" +
                               "     </td>" +
                               "   </tr>";
                               campos++;
                             var contenedor= document.getElementById("contenedorcampos");
                             contenedor.appendChild(NvoCampo);
                             document.getElementById('txtcontador').value=campos;
                     } else{
                         alert('La cantidad de cupos debe ser mayor a 0 para poderse registrar el horario');
                         return false;
                     }
                } else {
                    alert('Debe seleccionar una hora de salida al consultorio para ser agregado el horario al listado');
                    return false;
                }
            } else {
                    alert('Debe seleccionar una hora de entrada al consultorio para ser agregado el horario al listado');
                    return false;
            }
         } else {
             alert('Debe seleccionar una fecha para ser agregado el horario al listado');
             return false;
         }
     } else {
         alert('Debe seleccionar un consultorio para ser agregado el consultorio al listado');
         return false;
     }
  } else {
      alert('Debe Seleccionar un medico para ser agregado al listado');
      return false;
  }
}

function quitarCampo(iddiv){
  var eliminar = document.getElementById("divcampo_" + iddiv);
  var contenedor= document.getElementById("contenedorcampos");
  contenedor.removeChild(eliminar);
}

function verificar_cargados(id,medico,cons,consT,fecha,entrada,salida,fecha_fin,semanas,id_esp, esp){
    var encontro=false;
    var campos=$("#txtcontador").val();
    if (campos>0){
        for (var i=0;i<campos;i++){
            if (id==$("#id_med"+i).val())
                if (id_esp==$("#id_esp"+i).val())
                    if (cons==$("#consul_"+i).val())
                        if (fecha==$("#fecha_"+i).val())
                            if (entrada==$("#entrada_"+i).val())
                                if (salida==$("#salida_"+i).val())
                                    encontro=true;
        }
    }
    if (!encontro) 
        agregarCampo(id,medico,cons,consT,fecha,entrada,salida,fecha_fin,semanas, id_esp, esp);
    else 
        alert('El horario ya fue cargado anteriormente');
}

function isset () {
  var a = arguments,
    l = a.length,
    i = 0,
    undef;

  if (l === 0)
  {
    throw new Error('Empty isset');
  }

  while (i !== l)
  {
    if (a[i] === undef || a[i] === null)
    {
      return false;
    }
    i++;
  }
  return true;
}

//////////////////////////////////////////////////////////////////////////////////