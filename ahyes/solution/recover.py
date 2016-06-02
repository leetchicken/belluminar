#-*- coding:utf-8 -*-

from z3 import *

from aes import AES

encflag = "".join("81 74 45 2d f0 56 70 7d 15 82 d8 23 c2 e3 a2 d2".split()).decode("hex")
# k11
knownsubkey = "d044e824a4bdc5eb143c74fbc0491c64".decode("hex")
knownsubkey = map(ord, knownsubkey)
base = 16*11

AES = AES()
s = Solver()

# make sboxes z3-compatinle
rcon = AES.Rcon[::]
AES.Rcon = Array("rcon", BitVecSort(8), BitVecSort(8))
for x in xrange(255):
    s.add(AES.Rcon[x] == rcon[x])

sbox = AES.sbox[::]
AES.sbox = Array("sbox", BitVecSort(8), BitVecSort(8))
for x in xrange(256):
    s.add(AES.sbox[x] == sbox[x])

# compute symbolically
master = [BitVec("master%d" % i, 8) for i in xrange(16)]
exp = AES.expandKey(master, 16, 15*16)

# set constraint
for y in xrange(4):
    for x in xrange(4):
        s.add(exp[base + x*4 + y] == knownsubkey[x*4+y] ^ 0xca)

print "Solving"
print s.check()
model = s.model()
print "Model"
k = ""
for m in master:
    print model[m],
    k += chr(int(model[m].as_long()))
print
print `k`

from Crypto.Cipher import AES
c = AES.new(k, mode=AES.MODE_ECB)
print "flag", c.decrypt(encflag)
