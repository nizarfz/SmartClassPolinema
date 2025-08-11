from pydantic import BaseModel
from datetime import datetime

class datameter(BaseModel):
    vi: float
    ii: float
    pi: float
    fi:float
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

class datameterdb(BaseModel):
    id: int
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
    created: datetime

class smartclass(BaseModel):
    temperature:float
    humidity:float
    uv_status:str
    people_count:int
    lamp_status:str

class smartclassdb(BaseModel):
    id:int
    temperature:float
    humidity:float
    uv_status:str
    people_count:str
    lamp_status:str
    created:datetime