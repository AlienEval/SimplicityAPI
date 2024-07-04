gestuser
?token={token}: Valida un token, lo mueve de ok.txt a deleted.txt, y verifica el estado de la IP.

?ip=!: Registra la IP del cliente y verifica si está en la lista negra (blacklist.txt).

?addb={ip}: Agrega una IP a la lista negra (blacklist.txt).

?clearz=!: Limpia y recrea todos los archivos de texto del sistema.

?clearinfo=!: Limpia el contenido de info.txt.

?img={data}: Guarda datos recibidos en info.txt.

gestoken
?3d=users: Retorna una lista de nombres de archivos JSON en la carpeta /json/.

?clr={token}: Borra el archivo JSON correspondiente al token especificado.

?3d=clear: Borra todos los archivos JSON en la carpeta /json/.

?status=!: Responde con un mensaje de éxito en formato JSON.

delivr
?loadtokens=!: Copia los tokens del archivo ok.txt al archivo okload.txt.

?newtoken=!: Obtiene el primer token disponible del archivo okload.txt, lo añade al archivo delivered.txt, y lo elimina de okload.txt.

?verify={token}: Verifica si el token especificado existe en el archivo ok.txt.

/admin.php --> token editor
/help/creator.php --> token format creator
/rem/ctrl/ --> Rem Controller
