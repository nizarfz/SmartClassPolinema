import asyncpg

async def connect_db():
    return await asyncpg.create_pool(
        user='smartclass',
        password='elektroloss',
        database='smartclass',
        host='localhost',
        port=5432
    )
