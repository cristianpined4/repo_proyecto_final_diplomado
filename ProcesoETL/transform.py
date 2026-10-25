"""
Modulo de Transformacion (Transform) - Proceso ETL
Aplica las reglas de negocio, estandarizacion, tratamiento de nulos y modelado dimensional.
"""

import logging
import numpy as np
import pandas as pd

logger = logging.getLogger("ETL_Transform")


def transform_dim_sucursal(df: pd.DataFrame) -> pd.DataFrame:
    """Transforma el catalogo de sucursales a la dimension dw.dim_sucursal."""
    res = pd.DataFrame()
    res["sucursal_bk"] = df["id"].astype(int)
    res["nombre"] = df["nombre"].fillna("Sucursal Sin Nombre").astype(str)
    res["direccion"] = df["direccion"].fillna("Direccion no especificada").astype(str)
    res["telefono"] = df["telefono"].fillna("N/D").astype(str)
    return res


def transform_dim_producto(
    df_prod: pd.DataFrame,
    df_cat: pd.DataFrame,
    df_subcat: pd.DataFrame,
    df_marcas: pd.DataFrame,
    df_modelos: pd.DataFrame,
    df_unidades: pd.DataFrame,
) -> pd.DataFrame:
    """
    Transforma productos desnormalizando las jerarquias de categorias, marcas y unidades.
    """
    merged = df_prod.copy()
    
    # Cruces para desnormalizar catalogos
    if not df_cat.empty:
        cat_name_col = "name" if "name" in df_cat.columns else "nombre"
        merged = merged.merge(
            df_cat[["id", cat_name_col]].rename(columns={cat_name_col: "categoria_nombre"}),
            left_on="categoria_id",
            right_on="id",
            how="left",
            suffixes=("", "_cat"),
        )
    else:
        merged["categoria_nombre"] = "Sin Categoria"

    if not df_subcat.empty:
        merged = merged.merge(
            df_subcat[["id", "nombre"]].rename(columns={"nombre": "subcategoria_nombre"}),
            left_on="subcategoria_id",
            right_on="id",
            how="left",
            suffixes=("", "_subcat"),
        )
    else:
        merged["subcategoria_nombre"] = "Sin Subcategoria"

    if not df_marcas.empty:
        merged = merged.merge(
            df_marcas[["id", "nombre"]].rename(columns={"nombre": "marca_nombre"}),
            left_on="marca_id",
            right_on="id",
            how="left",
            suffixes=("", "_marca"),
        )
    else:
        merged["marca_nombre"] = "Sin Marca"

    if not df_modelos.empty:
        merged = merged.merge(
            df_modelos[["id", "nombre"]].rename(columns={"nombre": "modelo_nombre"}),
            left_on="modelo_id",
            right_on="id",
            how="left",
            suffixes=("", "_modelo"),
        )
    else:
        merged["modelo_nombre"] = "Sin Modelo"

    if not df_unidades.empty:
        merged = merged.merge(
            df_unidades[["id", "nombre"]].rename(columns={"nombre": "unidad_nombre"}),
            left_on="unidad_id",
            right_on="id",
            how="left",
            suffixes=("", "_unidad"),
        )
    else:
        merged["unidad_nombre"] = "Unidad"

    res = pd.DataFrame()
    res["producto_bk"] = merged["id"].astype(int)
    res["sku"] = merged["sku"].fillna("SKU-DESC").astype(str)
    res["nombre"] = merged["nombre"].fillna("Producto Sin Nombre").astype(str)
    res["categoria"] = merged["categoria_nombre"].fillna("Sin Categoria").astype(str)
    res["subcategoria"] = merged["subcategoria_nombre"].fillna("Sin Subcategoria").astype(str)
    res["marca"] = merged["marca_nombre"].fillna("Sin Marca").astype(str)
    res["modelo"] = merged["modelo_nombre"].fillna("Sin Modelo").astype(str)
    res["unidad_medida"] = merged["unidad_nombre"].fillna("Unidad").astype(str)
    res["costo_promedio"] = merged["costo_promedio"].fillna(0.0).astype(float)
    res["precio_venta"] = merged["precio_venta"].fillna(0.0).astype(float)
    res["aplica_iva"] = merged["aplica_iva"].fillna(True).astype(bool)
    res["tipo"] = merged["tipo"].fillna("no perecedero").astype(str)
    res["estado"] = np.where(merged["status"] == 1, "Activo", "Inactivo")

    return res


def transform_dim_cliente(df: pd.DataFrame) -> pd.DataFrame:
    """Transforma el maestro de clientes a la dimension dw.dim_cliente."""
    res = pd.DataFrame()
    res["cliente_bk"] = df["id"].astype(int)
    nombre = df["nombre"].fillna("").astype(str)
    apellido = df["apellido"].fillna("").astype(str) if "apellido" in df.columns else ""
    if "apellido" in df.columns:
        res["nombre_completo"] = (nombre + " " + apellido).str.strip()
    else:
        res["nombre_completo"] = nombre.str.strip()
    res["nombre_completo"] = res["nombre_completo"].replace("", "Cliente Desconocido")
    res["dui"] = df["dui"].fillna("N/D").astype(str) if "dui" in df.columns else "N/D"
    res["nit"] = df["nit"].fillna("N/D").astype(str) if "nit" in df.columns else "N/D"
    res["nrc"] = df["nrc"].fillna("N/D").astype(str) if "nrc" in df.columns else "N/D"
    res["telefono"] = df["telefono"].fillna("N/D").astype(str) if "telefono" in df.columns else "N/D"
    
    email_col = "correo" if "correo" in df.columns else "email"
    res["email"] = df[email_col].fillna("").astype(str) if email_col in df.columns else ""
    res["direccion"] = df["direccion"].fillna("N/D").astype(str) if "direccion" in df.columns else "N/D"
    return res


def transform_dim_proveedor(df: pd.DataFrame) -> pd.DataFrame:
    """Transforma el maestro de proveedores a la dimension dw.dim_proveedor."""
    res = pd.DataFrame()
    res["proveedor_bk"] = df["id"].astype(int)
    res["nombre"] = df["nombre"].fillna("Proveedor Sin Nombre").astype(str)
    res["nit"] = df["nit"].fillna("N/D").astype(str) if "nit" in df.columns else "N/D"
    res["nrc"] = df["nrc"].fillna("N/D").astype(str) if "nrc" in df.columns else "N/D"
    res["telefono"] = df["telefono"].fillna("N/D").astype(str) if "telefono" in df.columns else "N/D"
    
    email_col = "correo" if "correo" in df.columns else "email"
    res["email"] = df[email_col].fillna("").astype(str) if email_col in df.columns else ""
    res["direccion"] = df["direccion"].fillna("N/D").astype(str) if "direccion" in df.columns else "N/D"
    return res


def transform_dim_usuario(df: pd.DataFrame) -> pd.DataFrame:
    """Transforma los usuarios a la dimension dw.dim_usuario."""
    res = pd.DataFrame()
    res["usuario_bk"] = df["id"].astype(int)
    nombre = df["name"].fillna("").astype(str)
    apellido = df["lastname"].fillna("").astype(str) if "lastname" in df.columns else ""
    res["nombre_completo"] = (nombre + " " + apellido).str.strip().replace("", "Usuario Sistema")
    res["email"] = df["email"].fillna("").astype(str)
    return res


def transform_fact_ventas(
    df_ventas: pd.DataFrame,
    df_detalles: pd.DataFrame,
    df_productos: pd.DataFrame,
    dim_maps: dict,
) -> pd.DataFrame:
    """
    Transforma los detalles de ventas aplicando la regla de negocio:
    Solo se incluyen ventas PAGADAS para no distorsionar ingresos ni métricas.
    Calcula medidas derivadas: costo total, utilidad bruta y monto de IVA.
    """
    # 1. Filtro estricto de negocio: solo ventas pagadas
    ventas_validas = df_ventas[df_ventas["status"].astype(str).str.lower() == "pagada"].copy()
    logger.info("Ventas pagadas filtradas: %d de %d ventas totales", len(ventas_validas), len(df_ventas))

    # Renombrar columnas clave para evitar ambiguedades
    detalles = df_detalles.copy()
    if "id" in detalles.columns:
        detalles = detalles.rename(columns={"id": "detalle_id"})

    ventas = ventas_validas.copy()
    if "id" in ventas.columns:
        ventas = ventas.rename(columns={"id": "venta_id_pk"})
    if "created_at" in ventas.columns:
        ventas = ventas.rename(columns={"created_at": "fecha_venta"})

    # 2. Join con detalles de venta
    merged = detalles.merge(
        ventas,
        left_on="venta_id",
        right_on="venta_id_pk",
        how="inner",
    )

    # 3. Incorporar atributos del producto (costo promedio y aplica_iva)
    prod_info = df_productos[["id", "costo_promedio", "aplica_iva"]].copy().rename(columns={"id": "prod_id_pk"})
    merged = merged.merge(
        prod_info,
        left_on="producto_id",
        right_on="prod_id_pk",
        how="left",
    )

    # 4. Construccion de la tabla de hechos
    fact = pd.DataFrame()
    
    # Clave de fecha (AAAAMMDD)
    fecha_col = "fecha_venta" if "fecha_venta" in merged.columns else "created_at"
    fechas = pd.to_datetime(merged[fecha_col], errors="coerce") if fecha_col in merged.columns else pd.Series(pd.Timestamp.now(), index=merged.index)
    fact["fecha_sk"] = fechas.dt.strftime("%Y%m%d").fillna("20260101").astype(int)

    # Mapeo a claves subrogadas
    fact["sucursal_sk"] = merged["sucursal_id"].map(dim_maps.get("sucursal", {})).fillna(0).astype(int)
    fact["producto_sk"] = merged["producto_id"].map(dim_maps.get("producto", {})).fillna(0).astype(int)
    fact["cliente_sk"] = merged["cliente_id"].map(dim_maps.get("cliente", {})).fillna(0).astype(int)
    fact["usuario_sk"] = merged["user_id"].map(dim_maps.get("usuario", {})).fillna(0).astype(int)

    # Dimensiones degeneradas
    fact["venta_id_bk"] = merged["venta_id"].astype(int)
    fact["detalle_venta_id_bk"] = merged["detalle_id"].astype(int) if "detalle_id" in merged.columns else merged.index + 1
    
    serie = merged["serie"].fillna("").astype(str) if "serie" in merged.columns else pd.Series("", index=merged.index)
    correlativo = merged["correlativo"].fillna("").astype(str) if "correlativo" in merged.columns else pd.Series("", index=merged.index)
    fact["serie_correlativo"] = (serie + "-" + correlativo).str.strip("-").replace("", "N/D")
    
    fact["metodo_pago"] = merged["metodo_pago"].fillna("Efectivo").astype(str) if "metodo_pago" in merged.columns else "Efectivo"

    # Medidas
    fact["cantidad"] = merged["cantidad"].fillna(1).astype(int)
    fact["precio_unitario"] = merged["precio_venta"].fillna(0.0).astype(float)
    fact["subtotal_linea"] = (fact["cantidad"] * fact["precio_unitario"]).round(4)
    
    # IVA (13% en El Salvador si aplica_iva)
    aplica_iva = merged["aplica_iva"].fillna(True).astype(bool)
    fact["monto_iva_linea"] = np.where(aplica_iva, (fact["subtotal_linea"] * 0.13).round(4), 0.0)
    fact["total_linea"] = (fact["subtotal_linea"] + fact["monto_iva_linea"]).round(4)

    fact["costo_unitario"] = merged["costo_promedio"].fillna(0.0).astype(float)
    fact["costo_total"] = (fact["cantidad"] * fact["costo_unitario"]).round(4)
    fact["utilidad_bruta"] = (fact["subtotal_linea"] - fact["costo_total"]).round(4)

    logger.info("Lineas de detalle de ventas procesadas para fact_ventas: %d", len(fact))
    return fact


def transform_fact_compras(
    df_compras: pd.DataFrame,
    df_detalles: pd.DataFrame,
    dim_maps: dict,
) -> pd.DataFrame:
    """
    Transforma los detalles de compras aplicando regla de negocio:
    Solo se incluyen compras con estado PAGADO.
    """
    # 1. Filtro de negocio: compras pagadas
    compras_validas = df_compras[df_compras["status"].astype(str).str.lower() == "pagado"].copy()
    logger.info("Compras pagadas filtradas: %d de %d compras totales", len(compras_validas), len(df_compras))

    detalles = df_detalles.copy()
    if "id" in detalles.columns:
        detalles = detalles.rename(columns={"id": "detalle_id"})

    compras = compras_validas.copy()
    if "id" in compras.columns:
        compras = compras.rename(columns={"id": "compra_id_pk"})
    if "created_at" in compras.columns:
        compras = compras.rename(columns={"created_at": "fecha_compra"})

    # 2. Join con detalles
    merged = detalles.merge(
        compras,
        left_on="compra_id",
        right_on="compra_id_pk",
        how="inner",
    )

    fact = pd.DataFrame()
    fecha_col = "fecha_compra" if "fecha_compra" in merged.columns else "created_at"
    fechas = pd.to_datetime(merged[fecha_col], errors="coerce") if fecha_col in merged.columns else pd.Series(pd.Timestamp.now(), index=merged.index)
    fact["fecha_sk"] = fechas.dt.strftime("%Y%m%d").fillna("20260101").astype(int)

    fact["sucursal_sk"] = merged["sucursal_id"].map(dim_maps.get("sucursal", {})).fillna(0).astype(int)
    fact["producto_sk"] = merged["producto_id"].map(dim_maps.get("producto", {})).fillna(0).astype(int)
    fact["proveedor_sk"] = merged["proveedor_id"].map(dim_maps.get("proveedor", {})).fillna(0).astype(int)
    fact["usuario_sk"] = merged["user_id"].map(dim_maps.get("usuario", {})).fillna(0).astype(int)

    fact["compra_id_bk"] = merged["compra_id"].astype(int)
    fact["detalle_compra_id_bk"] = merged["detalle_id"].astype(int) if "detalle_id" in merged.columns else merged.index + 1
    fact["numero_factura"] = merged["numero_factura"].fillna("N/D").astype(str) if "numero_factura" in merged.columns else "N/D"

    fact["cantidad"] = merged["cantidad"].fillna(1).astype(int)
    fact["precio_compra"] = merged["precio_compra"].fillna(0.0).astype(float)
    fact["subtotal_linea"] = (fact["cantidad"] * fact["precio_compra"]).round(4)
    fact["monto_iva_linea"] = (fact["subtotal_linea"] * 0.13).round(4)
    fact["total_linea"] = (fact["subtotal_linea"] + fact["monto_iva_linea"]).round(4)

    logger.info("Lineas de detalle de compras procesadas para fact_compras: %d", len(fact))
    return fact


def transform_fact_inventario_snapshot(
    df_prod_suc: pd.DataFrame,
    df_productos: pd.DataFrame,
    dim_maps: dict,
) -> pd.DataFrame:
    """
    Construye el snapshot de inventario cruzando las existencias por sucursal
    con los costos y precios de venta vigentes.
    """
    merged = df_prod_suc.merge(
        df_productos[["id", "costo_promedio", "precio_venta"]],
        left_on="producto_id",
        right_on="id",
        how="left",
    )

    fact = pd.DataFrame(index=merged.index)
    fact["fecha_sk"] = int(pd.Timestamp.now().strftime("%Y%m%d"))
    fact["sucursal_sk"] = merged["sucursal_id"].map(dim_maps.get("sucursal", {})).fillna(0).astype(int)
    fact["producto_sk"] = merged["producto_id"].map(dim_maps.get("producto", {})).fillna(0).astype(int)
    
    stock_col = "stock_actual" if "stock_actual" in merged.columns else "stock"
    fact["stock_actual"] = merged[stock_col].fillna(0).astype(int)
    fact["costo_promedio"] = merged["costo_promedio"].fillna(0.0).astype(float)
    fact["precio_venta"] = merged["precio_venta"].fillna(0.0).astype(float)

    fact["valor_inventario_costo"] = (fact["stock_actual"] * fact["costo_promedio"]).round(4)
    fact["valor_inventario_venta"] = (fact["stock_actual"] * fact["precio_venta"]).round(4)
    fact["stock_bajo_alerta"] = fact["stock_actual"] < 15

    # Asegurar unicidad (fecha_sk, sucursal_sk, producto_sk)
    fact = fact.drop_duplicates(subset=["fecha_sk", "sucursal_sk", "producto_sk"])

    logger.info("Registros de snapshot de inventario procesados: %d", len(fact))
    return fact
