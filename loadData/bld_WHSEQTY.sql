select shadow_number,1,'', 0,0,0,0,0,0,0,0,0,0,0,0,cost,core
from PARTS
where p_l not in (
'NOF',
'WHD',
'WIX')
