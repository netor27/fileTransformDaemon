###Este proyecto debe estar ubicado en:
####/home/neto/fileTransformDaemon/

Se debe configurar como un upstart en ubuntu
-Crear el archivo unovaTransformer.conf en /etc/init/ con lo siguiente


description "unova transformer"
author "Ernesto Rubio"

start on runlevel [2345]
stop on starting rc RUNLEVEL=[016]

respawn
respawn limit 2 5

exec php /home/user/fileTransformDaemon/startTransform.php
