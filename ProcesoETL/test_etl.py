"""
Pruebas Unitarias y de Validacion para el Proceso ETL.
Valida conexiones, reglas de negocio dimensionales, transformaciones y consistencia de datos.
"""

import pytest
import pandas as pd
from ProcesoETL.config import test_connections as verify_connections, get_dw_engine
from ProcesoETL.transform import (
    transform_dim_sucursal,
    transform_dim_producto,
    transform_fact_ventas,
    transform_fact_inventario_snapshot,
)


def test_connections_active():
    """Valida que ambas bases de datos (OLTP y DW) respondan activamente."""
    status = verify_connections()
    assert status["oltp"] is True, f"Fallo conexion OLTP: {status['error_oltp']}"
    assert status["dw"] is True, f"Fallo conexion DW: {status['error_dw']}"


def test_transform_dim_sucursal_rules():
    """Valida la transformacion y tratamiento de nulos en sucursales."""
    df_raw = pd.DataFrame([
        {"id": 1, "nombre": "Casa Central", "direccion": "Av. Roosevelt", "telefono": "2600-0000"},
        {"id": 2, "nombre": None, "direccion": None, "telefono": None},
    ])
    df_dim = transform_dim_sucursal(df_raw)
    assert len(df_dim) == 2
    assert df_dim.loc[0, "sucursal_bk"] == 1
    assert df_dim.loc[1, "nombre"] == "Sucursal Sin Nombre"
    assert df_dim.loc[1, "telefono"] == "N/D"


def test_transform_dim_producto_hierarchy():
    """Valida la desnormalizacion y cruce de catalogos de productos."""
    df_prod = pd.DataFrame([{
        "id": 101, "sku": "PLAS-001", "nombre": "Bolsa Plastica 10x15",
        "categoria_id": 1, "subcategoria_id": 2, "marca_id": 3,
        "modelo_id": 4, "unidad_id": 5, "stock": 50,
        "costo_promedio": 1.25, "precio_venta": 2.50,
        "status": 1, "tipo": "no perecedero", "aplica_iva": True
    }])
    df_cat = pd.DataFrame([{"id": 1, "nombre": "Empaques"}])
    df_subcat = pd.DataFrame([{"id": 2, "nombre": "Bolsas"}])
    df_marca = pd.DataFrame([{"id": 3, "nombre": "PlastiSur"}])
    df_modelo = pd.DataFrame([{"id": 4, "nombre": "Standard"}])
    df_unidad = pd.DataFrame([{"id": 5, "nombre": "Fardo"}])

    df_dim = transform_dim_producto(df_prod, df_cat, df_subcat, df_marca, df_modelo, df_unidad)
    
    assert len(df_dim) == 1
    assert df_dim.loc[0, "producto_bk"] == 101
    assert df_dim.loc[0, "categoria"] == "Empaques"
    assert df_dim.loc[0, "subcategoria"] == "Bolsas"
    assert df_dim.loc[0, "marca"] == "PlastiSur"
    assert df_dim.loc[0, "unidad_medida"] == "Fardo"
    assert df_dim.loc[0, "estado"] == "Activo"


def test_fact_ventas_business_rules():
    """
    Valida la regla de negocio fundamental:
    1. Solo ventas 'pagada' son procesadas.
    2. Calculo de subtotal, IVA, costo total y utilidad bruta.
    """
    df_ventas = pd.DataFrame([
        {"id": 1, "cliente_id": 10, "user_id": 1, "sucursal_id": 2, "status": "pagada", "created_at": "2026-05-15 10:00:00"},
        {"id": 2, "cliente_id": 11, "user_id": 1, "sucursal_id": 2, "status": "anulada", "created_at": "2026-05-15 11:00:00"},
    ])
    df_detalles = pd.DataFrame([
        {"id": 1001, "venta_id": 1, "producto_id": 200, "cantidad": 10, "precio_venta": 5.0},
        {"id": 1002, "venta_id": 2, "producto_id": 200, "cantidad": 5, "precio_venta": 5.0},
    ])
    df_productos = pd.DataFrame([
        {"id": 200, "costo_promedio": 3.0, "aplica_iva": True}
    ])
    dim_maps = {
        "sucursal": {2: 12},
        "producto": {200: 50},
        "cliente": {10: 100},
        "usuario": {1: 5},
    }

    df_fact = transform_fact_ventas(df_ventas, df_detalles, df_productos, dim_maps)
    
    # Solo 1 linea valida (la de la venta pagada)
    assert len(df_fact) == 1
    row = df_fact.iloc[0]
    assert row["detalle_venta_id_bk"] == 1001
    assert row["cantidad"] == 10
    assert row["precio_unitario"] == 5.0
    assert row["subtotal_linea"] == 50.0
    assert row["monto_iva_linea"] == 6.5   # 13% de 50.0
    assert row["total_linea"] == 56.5
    assert row["costo_total"] == 30.0      # 10 * 3.0
    assert row["utilidad_bruta"] == 20.0   # 50.0 - 30.0


def test_fact_inventario_snapshot_alerts():
    """Valida la identificacion automatica de alertas de stock critico (< 15)."""
    df_prod_suc = pd.DataFrame([
        {"producto_id": 1, "sucursal_id": 1, "stock": 5},   # Critico
        {"producto_id": 2, "sucursal_id": 1, "stock": 100}, # Normal
    ])
    df_productos = pd.DataFrame([
        {"id": 1, "costo_promedio": 2.0, "precio_venta": 4.0},
        {"id": 2, "costo_promedio": 10.0, "precio_venta": 15.0},
    ])
    dim_maps = {"sucursal": {1: 1}, "producto": {1: 10, 2: 20}}

    df_inv = transform_fact_inventario_snapshot(df_prod_suc, df_productos, dim_maps)
    
    assert len(df_inv) == 2
    row_critico = df_inv[df_inv["producto_sk"] == 10].iloc[0]
    row_normal = df_inv[df_inv["producto_sk"] == 20].iloc[0]

    assert row_critico["stock_bajo_alerta"] is True or row_critico["stock_bajo_alerta"] == 1
    assert row_critico["valor_inventario_costo"] == 10.0 # 5 * 2.0
    assert row_normal["stock_bajo_alerta"] is False or row_normal["stock_bajo_alerta"] == 0
