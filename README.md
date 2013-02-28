fileTransformDaemon
===================
Este daemon es utilizado para realizar transformación de contenido.

Hace los siguientes pasos:

######Verifica la cola de mensajes en Amazon SQS
######Descarga el archivo original de Amazon S3
######Realiza la transformación a formato mp4 y ogv (video); mp3 y ogg (audio)
######Sube los archivos a Amazon S3, al bucket correspondiente del host que envió el mensaje
######Borra el archivo original de Amazon S3
######Envía mensajes de error por email
