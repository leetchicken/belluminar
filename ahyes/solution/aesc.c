#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <unistd.h>
#include <assert.h>
#include <fcntl.h>

// gcc -O3 -std=c11 aesc.c && time ./a.out
// real    4m14.116s

/*
    AES S-Box was xored with 0xca to harden recognition,
    the key schedule was modified to cancel this 0xca.
    So that the full encryption is equivalent to AES.
*/
#define MASKCONST 0xca
uint8_t sbox[256] = {
    169, 182, 189, 177, 56, 161, 165, 15, 250, 203, 173, 225, 52, 29, 97, 188, 0, 72, 3, 183, 48, 147, 141, 58, 103, 30, 104, 101, 86, 110, 184, 10, 125, 55, 89, 236, 252, 245, 61, 6, 254, 111, 47, 59, 187, 18, 251, 223, 206, 13, 233, 9, 210, 92, 207, 80, 205, 216, 74, 40, 33, 237, 120, 191, 195, 73, 230, 208, 209, 164, 144, 106, 152, 241, 28, 121, 227, 41, 229, 78, 153, 27, 202, 39, 234, 54, 123, 145, 160, 1, 116, 243, 128, 134, 146, 5, 26, 37, 96, 49, 137, 135, 249, 79, 143, 51, 200, 181, 154, 246, 85, 98, 155, 105, 138, 69, 88, 87, 242, 63, 118, 124, 16, 235, 218, 53, 57, 24, 7, 198, 217, 38, 149, 93, 142, 221, 14, 109, 180, 247, 174, 151, 211, 185, 170, 75, 133, 22, 232, 224, 90, 66, 140, 36, 114, 222, 20, 148, 193, 17, 42, 248, 240, 192, 131, 204, 238, 150, 8, 25, 102, 168, 91, 95, 46, 179, 45, 2, 253, 167, 71, 31, 132, 99, 166, 156, 62, 32, 175, 176, 100, 194, 112, 178, 239, 228, 214, 108, 126, 12, 34, 23, 190, 213, 129, 119, 65, 64, 186, 244, 127, 172, 130, 201, 60, 196, 171, 255, 157, 115, 76, 11, 215, 84, 43, 50, 82, 219, 163, 19, 68, 94, 81, 212, 77, 35, 4, 159, 226, 21, 70, 107, 67, 199, 117, 44, 136, 162, 139, 83, 231, 197, 122, 158, 113, 220
};
uint8_t rsbox[256] = {
    16, 89, 177, 18, 236, 95, 39, 128, 168, 51, 31, 221, 199, 49, 136, 7, 122, 159, 45, 229, 156, 239, 147, 201, 127, 169, 96, 81, 74, 13, 25, 181, 187, 60, 200, 235, 153, 97, 131, 83, 59, 77, 160, 224, 245, 176, 174, 42, 20, 99, 225, 105, 12, 125, 85, 33, 4, 126, 23, 43, 214, 38, 186, 119, 207, 206, 151, 242, 230, 115, 240, 180, 17, 65, 58, 145, 220, 234, 79, 103, 55, 232, 226, 249, 223, 110, 28, 117, 116, 34, 150, 172, 53, 133, 231, 173, 98, 14, 111, 183, 190, 27, 170, 24, 26, 113, 71, 241, 197, 137, 29, 41, 192, 254, 154, 219, 90, 244, 120, 205, 62, 75, 252, 86, 121, 32, 198, 210, 92, 204, 212, 164, 182, 146, 93, 101, 246, 100, 114, 248, 152, 22, 134, 104, 70, 87, 94, 21, 157, 132, 167, 141, 72, 80, 108, 112, 185, 218, 253, 237, 88, 5, 247, 228, 69, 6, 184, 179, 171, 0, 144, 216, 211, 10, 140, 188, 189, 3, 193, 175, 138, 107, 1, 19, 30, 143, 208, 44, 15, 2, 202, 63, 163, 158, 191, 64, 215, 251, 129, 243, 106, 213, 82, 9, 165, 56, 48, 54, 67, 68, 52, 142, 233, 203, 196, 222, 57, 130, 124, 227, 255, 135, 155, 47, 149, 11, 238, 76, 195, 78, 66, 250, 148, 50, 84, 123, 35, 61, 166, 194, 162, 73, 118, 91, 209, 37, 109, 139, 161, 102, 8, 46, 36, 178, 40, 217
};

// AES stuff
uint8_t state[4][4];
uint8_t roundkey[256];
int type2nkb[3] = {16, 24, 32};
int type2nkw[3] = {4, 6, 8};
int type_to_nrounds[3] = {10, 12, 14};

#define FORN(i, n) for(int i = 0; i < n; i++)
#define FOR(i, s, n) for(int i = s; i < n; i++)
#define RFOR(i, s, n) for(int i = (n)-1; i >= (s); i--)

uint8_t mul2(uint8_t x) {
    return (x<<1) ^ (((x>>7) & 1) * 0x1b);
}
uint8_t mul(uint8_t x, uint8_t y) {
    uint8_t ans = 0;
    while (x) {
        if (x & 1)
            ans ^= y;
        x >>= 1;
        y = mul2(y);
    }
    return ans;
}
uint8_t fexp2(int i) {
    uint8_t ans = 1;
    uint8_t r = 2;
    while (i) {
        if (i & 1)
            ans = mul(ans, r);
        r = mul(r, r);
        i >>= 1;
    }
    return ans;
}
void key_schedule(int type, char *key) {
    FORN(i, type2nkb[type]) {
        roundkey[i] = key[i] ^ MASKCONST;
    }
    int Nk = type2nkw[type];
    FOR(i, Nk, 256/4) {
        uint8_t t[4];
        FORN(j, 4)
            t[j] = roundkey[(i-1) * 4 + j] ^ MASKCONST;
        if (i % Nk == 0) {
            uint8_t tmp = t[0];
            t[0] = sbox[t[1]];
            t[1] = sbox[t[2]];
            t[2] = sbox[t[3]];
            t[3] = sbox[tmp];

            t[0] ^= fexp2(i/Nk-1);
        }
        else if (Nk > 6 && i % Nk == 4) {
            t[0] = sbox[t[0]];
            t[1] = sbox[t[1]];
            t[2] = sbox[t[2]];
            t[3] = sbox[t[3]];
        }
        else {
            FORN(j, t) t[j] ^= MASKCONST;
        }
        FORN(j, 4)
            roundkey[i*4 + j] = roundkey[(i-Nk)*4 + j] ^ t[j] ^ MASKCONST;;
    }
    FORN(i, type2nkb[type]) {
        roundkey[i] = key[i];
    }
}

#define SB() FORN(sby, 4) FORN(sbx, 4) state[sby][sbx] = sbox[state[sby][sbx]]
#define iSB() FORN(sby, 4) FORN(sbx, 4) state[sby][sbx] = rsbox[state[sby][sbx]]

#define SWAP(x, y) {x ^= y; y ^= x; x ^= y;}
#define SR1(sry) FORN(srx, 3) SWAP(state[sry][srx], state[sry][srx+1]);
#define SR() FORN(sry, 4) FORN(srn, sry) SR1(sry)
#define iSR() FORN(sri, 3) SR();

#define MC1(state, mcx) {uint8_t c[4] = {\
    mul(2,state[0][mcx])^mul(3,state[1][mcx])^mul(1,state[2][mcx])^mul(1,state[3][mcx]),\
    mul(1,state[0][mcx])^mul(2,state[1][mcx])^mul(3,state[2][mcx])^mul(1,state[3][mcx]),\
    mul(1,state[0][mcx])^mul(1,state[1][mcx])^mul(2,state[2][mcx])^mul(3,state[3][mcx]),\
    mul(3,state[0][mcx])^mul(1,state[1][mcx])^mul(1,state[2][mcx])^mul(2,state[3][mcx]),};\
    state[0][mcx]=c[0];state[1][mcx]=c[1];state[2][mcx]=c[2];state[3][mcx]=c[3];\
}
#define iMC1(state, mcx) {uint8_t c[4] = {\
    mul(14,state[0][mcx])^mul(11,state[1][mcx])^mul(13,state[2][mcx])^mul(9,state[3][mcx]),\
    mul(9,state[0][mcx])^mul(14,state[1][mcx])^mul(11,state[2][mcx])^mul(13,state[3][mcx]),\
    mul(13,state[0][mcx])^mul(9,state[1][mcx])^mul(14,state[2][mcx])^mul(11,state[3][mcx]),\
    mul(11,state[0][mcx])^mul(13,state[1][mcx])^mul(9,state[2][mcx])^mul(14 ,state[3][mcx]),};\
    state[0][mcx]=c[0];state[1][mcx]=c[1];state[2][mcx]=c[2];state[3][mcx]=c[3];\
}
#define MC() FORN(mcx, 4) MC1(state, mcx)
#define iMC() FORN(mcx, 4) iMC1(state, mcx)

#define AK(rno) FORN(aky, 4) FORN(akx, 4) state[aky][akx] ^= roundkey[rno*16 + akx*4 + aky]

#define TOSTATE(ptr) {FORN(y, 4) FORN(x, 4) state[y][x] = (ptr)[x*4+y];}
#define TOBUF(ptr) {FORN(y, 4) FORN(x, 4) (ptr)[x*4+y] = state[y][x];}

void encrypt_block(char * dest, char * src, int nrounds) {
    TOSTATE(src);
    AK(0);
    FOR(r, 1, nrounds) {
        SB();SR();MC();AK(r);
    }
    SB();SR();AK(nrounds);
    TOBUF(dest);
}
void decrypt_block(char * dest, char * src, int nrounds) {
    TOSTATE(src);
    AK(nrounds); iSR(); iSB();
    RFOR(r, 1, nrounds) {
        AK(r);iMC();iSR();iSB();
    }
    AK(0);
    TOBUF(dest);
}
void encrypt(char * dest, char * src, int length, char * key, int type, int nrounds) {
    key_schedule(type, key);
    FORN(i, (length + 15) / 16) {
        encrypt_block(dest+i*16, src+i*16, nrounds);
    }
}
void decrypt(char * dest, char * src, int length, char * key, int type, int nrounds) {
    key_schedule(type, key);
    FORN(i, (length + 15) / 16) {
        decrypt_block(dest+i*16, src+i*16, nrounds);
    }
}

#define NTEXTS 20

int main(int argc, char *argv[]) {
    uint8_t src[16] = "abcdefgh12345678";
    uint8_t dst[16] = {};
    uint8_t key[32] = "12345678abcdefghaaaabbbbccccdddd";

    printf("plain:");
    FORN(i, 16) printf(" %02x", src[i]);
    puts("");

    encrypt(dst, src, 16, key, 0, 10);
    printf(" ciph:");
    FORN(i, 16) printf(" %02x", dst[i]);
    puts("");

    decrypt(src, dst, 16, key, 0, 10);
    printf(" decr:");
    FORN(i, 16) printf(" %02x", src[i]);
    puts("");

    FORN(r, 16) {
        printf("roundkey %d:", r);
        FORN(i, 16)
            printf(" %02x", roundkey[r*16+i]);
        puts("");
    }
    printf("ciphertext:");
    FORN(i, 16)
        printf(" %02x", dst[i]);
    puts("");

    // Attack!
    uint8_t plain[NTEXTS * 16];
    uint8_t cipher10[NTEXTS * 16];
    uint8_t cipher12[NTEXTS * 16];

    if (0) { // generate new
        FORN(itr, NTEXTS) {
            memcpy(plain+itr*16, src, 16);
            plain[itr*16+0] += itr;
        }
        encrypt(cipher10, plain, NTEXTS*16, key, 0, 10); // normal
        encrypt(cipher12, plain, NTEXTS*16, key, 0, 12); // overflowed
    }
    else { // read from file
        int fd;
        // fd = open("plaintext.txt", 0);
        // assert(read(fd, plain, NTEXTS*16) == NTEXTS * 16);
        fd = open("ciph10.txt", 0);
        assert(read(fd, cipher10, NTEXTS*16) == NTEXTS * 16);
        fd = open("ciph12.txt", 0);
        assert(read(fd, cipher12, NTEXTS*16) == NTEXTS * 16);
    }

    // strip annoying linear layers
    FORN(itr, NTEXTS) {
        TOSTATE(cipher10 + 16*itr);
        MC();
        SR();
        TOBUF(cipher10 + 16*itr);

        TOSTATE(cipher12 + 16*itr);
        iSR();
        TOBUF(cipher12 + 16*itr);
    }

    // precompute possible differences of the S-Box
    char diff_possible[256][256] = {};
    FORN(x, 256) {
        FORN(dx, 256) {
            int dy = sbox[x ^ dx] ^ sbox[x];
            diff_possible[dx][dy] = 1;
        }
    }

    uint8_t final_key11[16] = {};
    FORN(x, 4) {
        printf("Checking x=%d\n", x);
        int k[4] = {};
        for(k[0] = 0; k[0] < 256; k[0]++){
            if ((k[0] & 0xf) == 0) printf("k0 %02x\n", k[0]);
        for(k[1] = 0; k[1] < 256; k[1]++){
        for(k[2] = 0; k[2] < 256; k[2]++){
        for(k[3] = 0; k[3] < 256; k[3]++){
            FORN(pi, NTEXTS - 1) {
                FOR(pj, pi + 1, NTEXTS) {
                    uint8_t si[4][4], sj[4][4];
                    FORN(y, 4) si[y][0] = sbox[cipher10[pi*16 + x*4+y] ^ k[y]];
                    FORN(y, 4) sj[y][0] = sbox[cipher10[pj*16 + x*4+y] ^ k[y]];

                    // Mix 1 column
                    MC1(si, 0); MC1(sj, 0);

                    FORN(y, 4) {
                        int dx = si[y][0] ^ sj[y][0];
                        int dy = cipher12[pi*16 + x*4+y] ^ cipher12[pj*16 + x*4+y];
                        if (!diff_possible[dx][dy])
                            goto BADk;
                    }
                }
            }

            // first key guessed, but we want the second one
            printf("candidate %02x %02x %02x %02x\n", k[0], k[1], k[2], k[3]);

            uint8_t ksk_maps[4][NTEXTS][2] = {};
            FORN(pi, NTEXTS) {
                uint8_t si[4][4];
                FORN(y, 4) si[y][0] = sbox[cipher10[pi*16 + x*4+y] ^ k[y]] ^ 0xca;
                MC1(si, 0);
                FORN(y, 4) ksk_maps[y][pi][0] = si[y][0];
                FORN(y, 4) ksk_maps[y][pi][1] = cipher12[pi*16 + x*4+y];
            }
            FORN(y, 4) {
                FORN(k1, 256) {
                FORN(k2, 256) {
                    FORN(pi, NTEXTS) {
                        uint8_t inp = ksk_maps[y][pi][0];
                        uint8_t out = ksk_maps[y][pi][1];
                        uint8_t test = sbox[inp ^ k1 ^ 0xca] ^ k2;
                        if (test != out)
                            goto BADk1k2;
                    }
                    final_key11[x*4+y] = k1;
                    printf("   possible key: x=%d y=%d k11 %02x\n", x, y, k1);
                    BADk1k2:;
                }}
            }

            // force break, we are pretty sure there's only one candidate
            goto NEXTx;
            BADk:;
        }}}}
        NEXTx:;
    }

    printf("recovered k11: ");
    FORN(i, 16) {
        printf("%02x", final_key11[i]);
    }
    printf("\n");

    return 0;
}
