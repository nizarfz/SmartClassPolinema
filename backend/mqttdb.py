
import json
from pydantic import BaseModel
import asyncpg
import asyncio
from asyncio_mqtt import Client, MqttError
from typing import List

# Database connection settings
DATABASE_URL = "postgresql://smartclass:elektroloss@localhost:5432/smartclass"
MQTT_BROKER = "localhost"
MQTT_PORT = 1883
MQTT_TOPIC = "smartmeter/lab1/tx"

# Pydantic model for sensor data
class datameter(BaseModel):
    ruang: str
    vi: float
    ii: float
    pi: float
    pfi: float
    vo1: float
    io1: float
    po1: float
    pfo1: float
    vo2: float
    io2: float
    po2: float
    pfo2: float
    ei: float
    eo1: float
    eo2: float

@app.on_event("startup")
async def startup():
    app.state.pool = await asyncpg.create_pool(DATABASE_URL)
    asyncio.create_task(mqtt_handler())

@app.on_event("shutdown")
async def shutdown():
    await app.state.pool.close()

async def mqtt_handler():
    while True:
        try:
            async with Client(MQTT_BROKER, port=MQTT_PORT) as client:
                await client.subscribe(MQTT_TOPIC)
                async with client.unfiltered_messages() as messages:
                    async for message in messages:
                        payload = message.payload.decode()
                        try:
                            data = json.loads(payload)
                            await save_sensor_data(data)
                        except Exception as e:
                            print(f"Error parsing message: {e}")
        except MqttError as error:
            print(f"MQTT Error '{error}', retrying in 5 seconds...")
            await asyncio.sleep(5)

async def save_sensor_data(data):
    query = """
    INSERT INTO sensor_data (sensor_id, temperature, humidity)
    VALUES ($1, $2, $3)
    """
    async with app.state.pool.acquire() as conn:
        await conn.execute(
            query,
            data.get("sensor_id"),
            data.get("temperature"),
            data.get("humidity")
        )

@app.get("/")
async def index():
    return {"status": "running", "mqtt": MQTT_TOPIC}


