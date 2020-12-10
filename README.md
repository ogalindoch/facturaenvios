# Factura envios

Los factura envío son una relación de envío con un pedido.
- El envío se puede cancelar, y re-surtirse, sin cancelar el pedido,  así que puede aparecer 2 veces el mismo pedido, con estatus (nuevo, descargado y cancelado)
- También eventualmente se quiere actualizar a descargado, para cuando se trabaje la cancelación se puede mandar alerta de que ya fue descargado.

## url `/facturaEnvios`
  Lista las entradas en tabla factura envios
## url `/facturaEnvios/{id}`
  Lista la entrada de factura envio con id # y sus partidas

Los factura envío pueden estar en varios estatus, filtros  (nuevo, descargado, cancelado)
