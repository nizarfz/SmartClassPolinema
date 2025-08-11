import asyncio
from fastapi import FastAPI
from fastapi.responses import JSONResponse
from app.database import connect_db
from app.mqtt_handler import mqtt_listener
from decimal import Decimal
from datetime import datetime
from fastapi.middleware.cors import CORSMiddleware
from datetime import datetime, timedelta
from typing import List

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allow all origins
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


def serialize(row):
    result = {}
    for key, value in dict(row).items():
        if isinstance(value, Decimal):
            result[key] = float(value)
        elif isinstance(value, datetime):
            result[key] = value.isoformat()  # Convert datetime to ISO string
        else:
            result[key] = value
    return result

@app.on_event("startup")
async def startup_event():
    app.state.pool = await connect_db()
    asyncio.create_task(mqtt_listener(app.state.pool))

@app.get("/")
async def read_root():
    return {"message": "MQTT FastAPI backend is running!"}

@app.get("/meter1/realtime")
async def get_sensor_data():
    query = """
    SELECT vi, ii, pi, created
    FROM smartmeter1
    ORDER BY created DESC
    LIMIT 1
    """
    async with app.state.pool.acquire() as conn:
        rows = await conn.fetch(query)

    data = [serialize(row) for row in rows]
    return JSONResponse(content=data)

@app.get("/meter2/realtime")
async def get_sensor_data():
    query = """
    SELECT vi, ii, pi, created
    FROM smartmeter2
    ORDER BY created DESC
    LIMIT 1
    """
    async with app.state.pool.acquire() as conn:
        rows = await conn.fetch(query)

    data = [serialize(row) for row in rows]
    return JSONResponse(content=data)

@app.get("/smartclass1/realtime")
async def get_sensor_data():
    query = """
    SELECT *
    FROM smartclass1
    ORDER BY created DESC
    LIMIT 1
    """
    async with app.state.pool.acquire() as conn:
        rows = await conn.fetch(query)

    data = [serialize(row) for row in rows]
    return JSONResponse(content=data)

@app.get("/realtime")
async def get_realtime():
    query1 = """
    SELECT id, vi, ii, pi, created
    FROM smartmeter1
    ORDER BY id DESC
    LIMIT 1
    """
    query2 = """
    SELECT id, vi, ii, pi, created
    FROM smartmeter2
    ORDER BY id DESC
    LIMIT 1
    """

    query3 = """
    SELECT *
    FROM smartclass1
    ORDER BY id DESC
    LIMIT 1
    """

    async with app.state.pool.acquire() as conn:
        row1 = await conn.fetchrow(query1)
        row2 = await conn.fetchrow(query2)
        row3 = await conn.fetchrow(query3)

    data = {
        "smartmeter1": serialize(row1) if row1 else None,
        "smartmeter2": serialize(row2) if row2 else None,
        "smartclass1": serialize(row3) if row3 else None,
    }

    return JSONResponse(content=data)

@app.get("/graph/realtime")
async def get_graph_realtime():
    query1 = """
    SELECT * FROM (
    SELECT * FROM public.smartmeter1 WHERE created >= NOW() - INTERVAL '5 minute' ORDER BY created DESC LIMIT 200
    ) sub
    ORDER BY created ASC;
    """
    query2 = """
    SELECT * FROM (
    SELECT * FROM public.smartmeter2 WHERE created >= NOW() - INTERVAL '5 minute' ORDER BY created DESC LIMIT 200
    ) sub
    ORDER BY created ASC;
    """
    query3 = """
    SELECT * FROM (
    SELECT * FROM public.smartclass1 ORDER BY created DESC LIMIT 100
    ) sub
    ORDER BY created ASC;
    """

    async with app.state.pool.acquire() as conn:
        rows1 = await conn.fetch(query1)
        rows2 = await conn.fetch(query2)
        rows3 = await conn.fetch(query3)

    data = {
        "smartmeter1": [serialize(row) for row in rows1],
        "smartmeter2": [serialize(row) for row in rows2],
        "smartclass1": [serialize(row) for row in rows3],
    }

    return JSONResponse(content=data, media_type="application/json; charset=utf-8")

@app.get("/graph/realtime/smartmeter")
async def get_graph_realtime():
    query1 = """
    SELECT * FROM (
    SELECT * FROM public.smartmeter1 WHERE created >= NOW() - INTERVAL '5 minute' ORDER BY created DESC LIMIT 200
    ) sub
    ORDER BY created ASC;
    """
    query2 = """
    SELECT * FROM (
    SELECT * FROM public.smartmeter2 WHERE created >= NOW() - INTERVAL '5 minute' ORDER BY created DESC LIMIT 200
    ) sub
    ORDER BY created ASC;
    """
    async with app.state.pool.acquire() as conn:
        rows1 = await conn.fetch(query1)
        rows2 = await conn.fetch(query2)

    data = {
        "smartmeter1": [serialize(row) for row in rows1],
        "smartmeter2": [serialize(row) for row in rows2],
    }

    return JSONResponse(content=data, media_type="application/json; charset=utf-8")

@app.get("/graph/realtime/smartclass")
async def get_graph_realtime():
    query1 = """
    SELECT * FROM (
    SELECT * FROM public.smartclass1 ORDER BY created DESC LIMIT 200
    ) sub
    ORDER BY created ASC;
    """
    async with app.state.pool.acquire() as conn:
        rows1 = await conn.fetch(query1)

    data = {
        "smartclass1": [serialize(row) for row in rows1],
    }

    return JSONResponse(content=data, media_type="application/json; charset=utf-8")

@app.get("/graph/realtime/smartmeter/{selector}")
async def get_graph_realtime(selector):
    interval_map = {
        "2jam": "2 hour",
        "12jam": "12 hour",
        "1hari": "1 day",
        "1minggu": "1 week",
        "1bulan": "1 month"
    }
    selected = interval_map.get(selector, "1 hour")

    query1 = f"""
    WITH filtered_data AS (
        SELECT created, vi, ii, pi
        FROM public.smartmeter1
        WHERE created >= NOW() - INTERVAL '{selected}'
    ),
    time_bounds AS (
        SELECT MIN(created) AS min_time, MAX(created) AS max_time FROM filtered_data
    ),
    bucketed_data AS (
        SELECT created, vi, ii, pi,
            FLOOR(
                EXTRACT(EPOCH FROM (created - (SELECT min_time FROM time_bounds))) /
                NULLIF(EXTRACT(EPOCH FROM ((SELECT max_time FROM time_bounds) - (SELECT min_time FROM time_bounds))), 0) * 200
            ) AS bucket
        FROM filtered_data
    )
    SELECT bucket, AVG(vi) AS vi, AVG(ii) AS ii, AVG(pi) AS pi, MIN(created) AS created
    FROM bucketed_data
    GROUP BY bucket
    ORDER BY bucket;
    """

    query2 = query1.replace("smartmeter1", "smartmeter2")

    async with app.state.pool.acquire() as conn:
        rows1 = await conn.fetch(query1)
        rows2 = await conn.fetch(query2)

    data = {
        "smartmeter1": [serialize(row) for row in rows1],
        "smartmeter2": [serialize(row) for row in rows2],
    }

    return JSONResponse(content=data, media_type="application/json; charset=utf-8")

@app.get("/graph/realtime/smartclass/{selector}")
async def get_graph_realtime(selector):
    interval_map = {
        "12jam": "12 hour",
        "1hari": "1 day",
        "1minggu": "1 week",
        "1bulan": "1 month"
    }
    selected = interval_map.get(selector, "1 hour")

    query1 = f"""
    WITH filtered_data AS (
        SELECT created, temperature, humidity, people_count
        FROM public.smartclass1
        WHERE created >= NOW() - INTERVAL '{selected}'
    ),
    time_bounds AS (
        SELECT MIN(created) AS min_time, MAX(created) AS max_time FROM filtered_data
    ),
    bucketed_data AS (
        SELECT created, temperature, humidity, people_count,
            FLOOR(
                EXTRACT(EPOCH FROM (created - (SELECT min_time FROM time_bounds))) /
                NULLIF(EXTRACT(EPOCH FROM ((SELECT max_time FROM time_bounds) - (SELECT min_time FROM time_bounds))), 0) * 200
            ) AS bucket
        FROM filtered_data
    )
    SELECT bucket, AVG(temperature) AS temperature, AVG(humidity) AS humidity, AVG(people_count) AS people_count, MIN(created) AS created
    FROM bucketed_data
    GROUP BY bucket
    ORDER BY bucket;
    """

    async with app.state.pool.acquire() as conn:
        rows1 = await conn.fetch(query1)

    data = {
        "smartclass1": [serialize(row) for row in rows1],
    }

    return JSONResponse(content=data, media_type="application/json; charset=utf-8")