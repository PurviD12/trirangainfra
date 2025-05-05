import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TransferLeadComponent } from './transfer-lead.component';

describe('TransferLeadComponent', () => {
  let component: TransferLeadComponent;
  let fixture: ComponentFixture<TransferLeadComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TransferLeadComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TransferLeadComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
