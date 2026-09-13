-- Actualización de la tabla usuarios para permitir el rol de supervisor
ALTER TABLE `usuarios` MODIFY COLUMN `rol` ENUM('cliente', 'admin', 'supervisor') DEFAULT 'cliente';
