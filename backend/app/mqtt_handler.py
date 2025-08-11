import json
import asyncio
import aiomqtt
from app.models import datameter, smartclass

ALLOWED_METERS = {"meter1", "meter2"}

async def save_meter_data(pool, meter_name: str, meter_data: dict):
    if meter_name not in ALLOWED_METERS:
        print(f"Invalid meter name: {meter_name}")
        return

    payload = datameter(**meter_data)
    #print(payload)

    query = f"""
        INSERT INTO smart{meter_name} (vi, ii, pi, fi, pfi, vo1, io1, po1, pfo1,
                                  vo2, io2, po2, pfo2, ei, eo1, eo2)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)
    """

    async with pool.acquire() as conn:
        await conn.execute(query,
            payload.vi, payload.ii, payload.pi, payload.fi, payload.pfi,
            payload.vo1, payload.io1, payload.po1, payload.pfo1,
            payload.vo2, payload.io2, payload.po2, payload.pfo2,
            payload.ei, payload.eo1, payload.eo2
        )

async def save_smartclass_data(pool, smartclass_data: dict):
    payload = smartclass(**smartclass_data)

    query = """
        INSERT INTO smartclass1 (temperature, humidity, uv_status, people_count, lamp_status)
        VALUES ($1, $2, $3, $4, $5)
    """

    async with pool.acquire() as conn:
        await conn.execute(query,
            payload.temperature, payload.humidity, payload.uv_status, payload.people_count, payload.lamp_status
        )

TOPIC_DISPATCHER = {
    "smartmeter/tx": save_meter_data,
    "/smartclass": save_smartclass_data,
}

async def mqtt_listener(pool):
    while True:
        try:
            async with aiomqtt.Client("localhost") as client:
                await client.subscribe("smartmeter/tx")
                await client.subscribe("/smartclass")

                async for message in client.messages:
                    topic = message.topic
                    payload_text = message.payload.decode()
                    #print(f"Received topic: '{topic}'")
                    #print(f"Received message: '{payload_text}'")

                    try:
                        data = json.loads(payload_text)
                        topic_str = str(message.topic)
                        handler = TOPIC_DISPATCHER.get(topic_str)


                        if not handler:
                            print(f"No handler for topic {topic}")
                            continue

                        if topic_str == "smartmeter/tx":
                            for meter_name, meter_values in data.items():
                                await handler(pool, meter_name, meter_values)

                        elif topic_str == "/smartclass":
                            await handler(pool, data)

                    except Exception as e:
                        print(f"Error processing {topic}: {e}")

        except Exception as e:
            print(f"MQTT connection error: {e}. Reconnect after 5s")
            await asyncio.sleep(5)
