.head on
.mode csv

-- best and worst quality of newspapers
select pub,
       100.0*sum(mBad)/sum(mTotal) as mBadPercent,
       100.0*sum(mUnknown)/sum(mTotal) as mUnknownPercent,
       100.0*sum(mTotal-mBad-mUnknown)/sum(mTotal) as mOther,
       sum(mTotal) as total
from newspaper_count group by pub;

--select count(distinct pub) from newspaper_count;

